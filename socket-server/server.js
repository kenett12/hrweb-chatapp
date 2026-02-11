process.env.TZ = "Asia/Manila";

import express from "express";
import { createServer } from "http";
import { Server } from "socket.io";
import cors from "cors";
import mysql from "mysql2/promise"; 

const app = express();
app.use(cors());
app.use(express.json());

// ==============================
// HEALTH CHECK (Fixes 404 & SyntaxError)
// ==============================
app.get('/health', (req, res) => {
    res.status(200).json({ status: 'ok', uptime: process.uptime() });
});

// ==============================
// XAMPP DATABASE CONNECTION
// ==============================
const dbConfig = {
    host: "localhost",
    user: "root",
    password: "", 
    database: "chat-app" 
};

let pool;
try {
    pool = mysql.createPool({
        ...dbConfig,
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0
    });
    console.log("🗄️ MySQL Pool Created");
} catch (err) {
    console.error("❌ MySQL Pool Error:", err);
}

const server = createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*", 
        methods: ["GET", "POST"],
        credentials: true,
    },
    path: "/socket.io",
    transports: ["websocket", "polling"],
});

// ==============================
// 1. PHP API INTEGRATION
// ==============================
app.post('/emit-message', (req, res) => {
    const { event, data } = req.body;

    if (!event || !data) {
        return res.status(400).json({ error: 'Missing event or data' });
    }

    console.log(`📩 Received event from PHP: ${event}`);

    switch (event) {
        case 'new_ticket_message':
            io.to(`ticket_${data.ticket_id}`).emit('new_ticket_message', data.message);
            io.to(`user_${data.message.sender_id}`).emit('update_ticket_list');
            break;

        case 'ticket_created':
            io.emit('ticket_created', data.ticket);
            break;

        case 'new_message':
            const roomName = getChatRoomName(data.sender_id, data.receiver_id);
            io.to(roomName).emit("receive_message", data);
            io.to(`user_${data.receiver_id}`).emit("receive_notification", data);
            break;

        case 'messages_read':
        case 'message_seen':
        case 'status_change':
            if (data.user_id) io.emit(event, data);
            break;
            
        default:
            io.emit(event, data);
            break;
    }

    res.json({ success: true });
});

// ==============================
// HELPERS
// ==============================
const getChatRoomName = (id1, id2) => {
    return [String(id1), String(id2)].sort().join('-');
};

async function logCall(data) {
    const { caller_id, target_id, type, status, duration } = data;
    
    if (!caller_id || !target_id) return;

    try {
        const query = `
            INSERT INTO call_logs (caller_id, receiver_id, call_type, status, duration_seconds, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        `;
        await pool.execute(query, [
            String(caller_id), 
            String(target_id), 
            type || 'audio', 
            status || 'completed', 
            duration || 0
        ]);
        console.log(`✅ LOGGED: ${status} call from ${caller_id} to ${target_id} (${duration}s)`);
        
        io.to(`user_${caller_id}`).emit("refresh_call_logs");
        io.to(`user_${target_id}`).emit("refresh_call_logs");

    } catch (err) {
        console.error("❌ DB Logging Error:", err.message);
    }
}

// ==============================
// TRACK ONLINE USERS
// ==============================
const onlineUsers = new Map();

io.on("connection", (socket) => {
    console.log(`🔌 New Connection: ${socket.id}`);

    socket.on("user_connected", (userId) => {
        const userIdStr = String(userId);
        if (!userIdStr || userIdStr === 'null') return;
        
        const personalRoom = `user_${userIdStr}`;
        socket.join(personalRoom);
        socket.userId = userIdStr;
        onlineUsers.set(userIdStr, socket.id);
        
        io.emit("user_status_change", { user_id: userId, status: 'online' });
    });

    socket.on("join_ticket", (ticketId) => {
        const room = `ticket_${ticketId}`;
        socket.join(room);
        console.log(`🎫 Socket ${socket.id} joined ticket room: ${room}`);
    });

    socket.on("leave_ticket", (ticketId) => {
        const room = `ticket_${ticketId}`;
        socket.leave(room);
    });

    socket.on("status_change", (data) => {
        io.emit("user_status_change", data);
    });

    socket.on("join_direct_chat", (data) => {
        const roomName = data.room_name || getChatRoomName(data.user_id, data.other_user_id);
        socket.join(roomName);
    });

    socket.on("new_message", (message) => {
        const roomName = getChatRoomName(message.sender_id, message.receiver_id);
        io.to(roomName).emit("receive_message", message);
        io.to(`user_${message.receiver_id}`).emit("receive_notification", message);
    });

    socket.on("typing", (data) => {
        const roomName = getChatRoomName(data.user_id, data.receiver_id);
        socket.to(roomName).emit("typing", data);
    });

    socket.on("call_request", (data) => {
        const targetRoom = `user_${String(data.target_id)}`;
        io.to(targetRoom).emit("call_request", data);
    });

    socket.on("call_accepted", (data) => {
        const targetRoom = `user_${String(data.target_id)}`;
        io.to(targetRoom).emit("call_accepted", {
            sdp: data.sdp,
            accepter_id: String(data.accepter_id),
            target_id: data.target_id
        });
    });

    socket.on("call_rejected", (data) => {
        const targetId = String(data.target_id);
        io.to(`user_${targetId}`).emit("call_rejected");
        
        logCall({
            caller_id: targetId, 
            target_id: socket.userId,  
            type: 'audio',
            status: 'rejected',
            duration: 0
        });
    });

    socket.on("ice_candidate", (data) => {
        const targetRoom = `user_${String(data.target_id)}`;
        io.to(targetRoom).emit("ice_candidate", data.candidate);
    });

    socket.on("call_ended", (data) => {
        const targetId = String(data.target_id);
        io.to(`user_${targetId}`).emit("call_ended");

        logCall({
            caller_id: socket.userId,
            target_id: targetId,
            type: data.type || 'audio',
            status: data.status || 'completed',
            duration: data.duration || 0
        });
    });

    socket.on("disconnect", () => {
        if (socket.userId && onlineUsers.get(socket.userId) === socket.id) {
            onlineUsers.delete(socket.userId);
            io.emit("user_status_change", { user_id: socket.userId, status: 'offline' });
        }
    });
});

server.listen(3001, () => console.log("🚀 Server running on port 3001"));