<div id="global-call-overlay" class="hidden">
    <div class="call-backdrop"></div>
    <div class="call-container">
        <div class="avatar-wrapper">
            <img id="gc-avatar" src="" alt="Avatar">
            <div class="pulse-ring"></div>
        </div>
        <h2 id="gc-name">User</h2>
        <p id="gc-status" class="status-text">Calling...</p>
        <div id="gc-timer" class="call-timer hidden">00:00</div>
        <div id="gc-actions-incoming" class="action-row">
            <button class="btn-action btn-decline" onclick="CallManager.rejectCall()"><i class="fas fa-phone-slash"></i><span>Decline</span></button>
            <button class="btn-action btn-accept" onclick="CallManager.acceptCall()"><i class="fas fa-phone"></i><span>Accept</span></button>
        </div>
        <div id="gc-actions-ongoing" class="action-row hidden">
            <button class="btn-action btn-decline" onclick="CallManager.endCall()"><i class="fas fa-phone-slash"></i><span>End Call</span></button>
        </div>
    </div>
</div>

<style>
    #global-call-overlay { position: fixed !important; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(10, 10, 10, 0.98) !important; z-index: 2147483647 !important; display: flex !important; align-items: center !important; justify-content: center !important; color: white !important; font-family: 'Inter', sans-serif; backdrop-filter: blur(20px); }
    #global-call-overlay.hidden { display: none !important; }
    .avatar-wrapper { position: relative; margin-bottom: 30px; }
    #gc-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #3b82f6; position: relative; z-index: 5; }
    .pulse-ring { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 150px; height: 150px; border-radius: 50%; border: 3px solid #3b82f6; animation: gc-pulse 2s infinite; z-index: 1; }
    @keyframes gc-pulse { 0% { width: 150px; height: 150px; opacity: 1; } 100% { width: 400px; height: 400px; opacity: 0; } }
    .status-text { font-size: 1.3rem; opacity: 0.6; margin-bottom: 50px; }
    .action-row { display: flex; gap: 60px; }
    .btn-action { display: flex; flex-direction: column; align-items: center; background: none; border: none; cursor: pointer; color: white; transition: transform 0.2s; }
    .btn-action:hover { transform: scale(1.1); }
    .btn-action i { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 10px; }
    .btn-decline i { background: #ff3b30; }
    .btn-accept i { background: #34c759; }
</style>

<script>
const CallManager = {
    currentCall: null,
    ringtone: new Audio('<?= base_url("public/assets/sounds/ringtone.mp3") ?>'),
    updateStatus: function(text) { 
        const el = document.getElementById('gc-status');
        if (el) el.innerText = text; 
    },
    showIncoming: function(data) {
        this.currentCall = data;
        document.getElementById('gc-name').innerText = data.caller_name || 'User';
        document.getElementById('gc-avatar').src = '<?= base_url("public/uploads/avatars/") ?>' + (data.caller_avatar || 'default-avatar.png');
        document.getElementById('gc-actions-incoming').classList.remove('hidden');
        document.getElementById('gc-actions-ongoing').classList.add('hidden');
        document.getElementById('global-call-overlay').classList.remove('hidden');
        this.ringtone.loop = true;
        this.ringtone.play().catch(() => {});
    },
    showOutgoing: function(name, avatar) {
        document.getElementById('gc-name').innerText = name;
        document.getElementById('gc-avatar').src = avatar;
        this.updateStatus("Calling...");
        document.getElementById('gc-actions-incoming').classList.add('hidden');
        document.getElementById('gc-actions-ongoing').classList.remove('hidden');
        document.getElementById('global-call-overlay').classList.remove('hidden');
    },
    acceptCall: function() {
        this.ringtone.pause();
        const type = this.currentCall.type || 'audio';
        window.open('<?= base_url("calls/") ?>' + type + '/' + this.currentCall.caller_id, 'ActiveCall', 'width=1280,height=720');
        this.updateStatus("Connected");
        document.getElementById('gc-actions-incoming').classList.add('hidden');
    },
    rejectCall: function() {
        this.ringtone.pause();
        if (window.socket && this.currentCall) {
            window.socket.emit('reject_call', { caller_id: this.currentCall.caller_id, receiver_id: window.currentUser.id });
        }
        this.resetUI('Declined');
    },
    endCall: function() { this.resetUI('Ended'); },
    resetUI: function(msg) {
        this.ringtone.pause();
        this.updateStatus(msg);
        setTimeout(() => { document.getElementById('global-call-overlay').classList.add('hidden'); }, 2000);
    }
};
</script>