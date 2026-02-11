// WebRTC functionality for audio/video calling
document.addEventListener("DOMContentLoaded", () => {
  // Global variables
  let localStream = null
  let peerConnection = null
  let remoteStream = null
  let callInProgress = false
  let isAudioCall = false
  let currentCallUser = null
  let callingDialog = null
  let ringtone = null
  let callTimer = null
  let callDuration = 0
  let isMuted = false
  let isVideoOff = false

  // DOM elements - will be initialized when needed
  let callModal = null
  let localVideo = null
  let remoteVideo = null
  let callStatus = null
  let callTimerElement = null
  let muteBtn = null
  let videoBtn = null
  let endCallBtn = null

  // ICE servers configuration for WebRTC
  const iceServers = {
    iceServers: [
      { urls: "stun:stun.l.google.com:19302" },
      { urls: "stun:stun1.l.google.com:19302" },
      { urls: "stun:stun2.l.google.com:19302" },
    ],
  }

  // Debug helper function
  function debugLog(message, data) {
    const timestamp = new Date().toISOString()
    console.log(`[${timestamp}] [WebRTC] ${message}`, data || "")
  }

  // Format time for call duration
  function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60
    return `${minutes.toString().padStart(2, "0")}:${remainingSeconds.toString().padStart(2, "0")}`
  }

  // Update the initCallUI function to create a better call UI
  function initCallUI() {
    console.log("Initializing call UI elements")

    // Check if call modal already exists
    if (document.getElementById("call-modal")) {
      callModal = document.getElementById("call-modal")
      localVideo = document.getElementById("local-video")
      remoteVideo = document.getElementById("remote-video")
      callStatus = document.getElementById("call-status")
      callTimerElement = document.getElementById("call-timer")
      muteBtn = document.getElementById("mute-btn")
      videoBtn = document.getElementById("video-btn")
      endCallBtn = document.getElementById("end-call-btn")
      return
    }

    // Create call modal
    callModal = document.createElement("div")
    callModal.id = "call-modal"
    callModal.className = "call-modal hidden"

    // Create call UI
    callModal.innerHTML = `
    <div class="call-modal-header">
      <div>
        <div id="call-status" class="call-status">Connecting...</div>
        <div id="call-timer" class="call-timer">00:00</div>
      </div>
    </div>
    <div class="call-modal-body">
      <div class="remote-video-container">
        <video id="remote-video" class="remote-video" autoplay playsinline></video>
        <div class="audio-only-container hidden">
          <div class="audio-only-avatar">
            <i class="fas fa-user"></i>
          </div>
          <div id="caller-name" class="caller-name">User</div>
          <div id="call-status-text" class="call-status-text">In call</div>
        </div>
      </div>
      <div class="local-video-container">
        <video id="local-video" class="local-video" autoplay playsinline muted></video>
      </div>
      <div class="call-controls">
        <button id="mute-btn" class="call-control-btn mute" title="Mute">
          <i class="fas fa-microphone"></i>
        </button>
        <button id="video-btn" class="call-control-btn video" title="Turn off video">
          <i class="fas fa-video"></i>
        </button>
        <button id="end-call-btn" class="call-control-btn end-call" title="End call">
          <i class="fas fa-phone-slash"></i>
        </button>
      </div>
    </div>
  `

    // Add to DOM - append to body
    document.body.appendChild(callModal)

    // Initialize elements
    localVideo = document.getElementById("local-video")
    remoteVideo = document.getElementById("remote-video")
    callStatus = document.getElementById("call-status")
    callTimerElement = document.getElementById("call-timer")
    muteBtn = document.getElementById("mute-btn")
    videoBtn = document.getElementById("video-btn")
    endCallBtn = document.getElementById("end-call-btn")

    // Add event listeners
    muteBtn.addEventListener("click", toggleAudio)
    videoBtn.addEventListener("click", toggleVideo)
    endCallBtn.addEventListener("click", endCall)
  }

  // Update the createCallingDialog function to include the user's avatar
  function createCallingDialog(recipient) {
    // Remove existing dialog if any
    if (callingDialog) {
      document.body.removeChild(callingDialog)
    }

    // Get recipient name and avatar
    let recipientName = "User"
    let recipientAvatar = "public/uploads/avatars/default-avatar.png"

    if (window.activeChat) {
      if (window.activeChat.name) {
        recipientName = window.activeChat.name
      }
      if (window.activeChat.avatar) {
        recipientAvatar = `public/uploads/avatars//${window.activeChat.avatar}`
      }
    }

    callingDialog = document.createElement("div")
    callingDialog.className = "calling-dialog"
    callingDialog.innerHTML = `
    <div class="calling-content">
      <div class="calling-avatar">
        <img src="${recipientAvatar}" alt="${recipientName}" class="caller-avatar-img">
      </div>
      <div class="calling-name">${recipientName}</div>
      <div class="calling-status">Calling...</div>
      <div class="calling-actions">
        <button id="cancel-call-btn" class="calling-btn">
          <i class="fas fa-phone-slash"></i>
        </button>
      </div>
    </div>
  `

    document.body.appendChild(callingDialog)

    // Add event listener to cancel button
    document.getElementById("cancel-call-btn").addEventListener("click", () => {
      endCall()
      document.body.removeChild(callingDialog)
      callingDialog = null
    })

    return callingDialog
  }

  // Add call buttons to chat header
  function addCallButtons() {
    // Check if we're in a direct chat (not a group)
    const chatHeaderInfo = document.querySelector(".chat-header-info")
    if (!chatHeaderInfo) return

    // Don't add call buttons if they already exist
    if (document.getElementById("call-buttons")) return

    // Create call buttons container
    const callButtons = document.createElement("div")
    callButtons.id = "call-buttons"
    callButtons.className = "call-buttons"
    callButtons.innerHTML = `
      <button id="audio-call-btn" class="call-btn audio-call" title="Start audio call">
        <i class="fas fa-phone"></i>
      </button>
      <button id="video-call-btn" class="call-btn video-call" title="Start video call">
        <i class="fas fa-video"></i>
      </button>
    `

    // Insert after the chat header info
    chatHeaderInfo.parentNode.insertBefore(callButtons, chatHeaderInfo.nextSibling)

    // Add event listeners
    document.getElementById("audio-call-btn").addEventListener("click", () => startCall(true))
    document.getElementById("video-call-btn").addEventListener("click", () => startCall(false))

    console.log("Call buttons added to chat header")
  }

  // Add this helper function to get the current user ID
  function getCurrentUserId() {
    // Try to get from session storage or other sources
    if (window.userId) {
      return window.userId
    }

    // Try to get from the DOM
    const userIdElement = document.querySelector('meta[name="user-id"]')
    if (userIdElement) {
      return userIdElement.getAttribute("content")
    }

    // Try to get from PHP session variable that might be embedded in the page
    let PHP_USER_ID
    if (typeof window.PHP_USER_ID !== "undefined") {
      PHP_USER_ID = window.PHP_USER_ID
      return PHP_USER_ID
    }

    // Last resort - try to extract from the URL
    const url = window.location.pathname
    const matches = url.match(/\/chat\/direct\/(\d+)/)
    if (matches && matches.length > 1) {
      // This is the other user's ID, not the current user
      return null
    }

    return null
  }

  // Initialize WebRTC
  function initWebRTC() {
    debugLog("Initializing WebRTC")

    // Initialize call UI
    initCallUI()

    // Add call buttons to chat header
    addCallButtons()

    // Initialize ringtone
    ringtone = new Audio("/assets/ringtone.mp3")
    ringtone.loop = true

    // Always use port 3001 for socket connection
    const socketUrl = "http://localhost:3001"

    // Listen for call events from socket
    if (window.socket) {
      debugLog("Using existing socket connection")
      setupSocketListeners(window.socket)
    } else {
      debugLog("No existing socket, attempting to connect to " + socketUrl)
      try {
        // Try to connect to the socket server
        const socket = io(socketUrl, {
          transports: ["websocket", "polling"],
          reconnection: true,
          reconnectionAttempts: 10,
          reconnectionDelay: 1000,
          timeout: 20000,
        })

        socket.on("connect", () => {
          debugLog("Socket connected successfully with ID: " + socket.id)
          window.socket = socket
          setupSocketListeners(socket)

          // Send user connected event
          const userId = getCurrentUserId()
          if (userId) {
            socket.emit("user_connected", userId)
            debugLog("Emitted user_connected event with ID: " + userId)
          }
        })

        socket.on("connect_error", (error) => {
          debugLog("Socket connection error: " + error.message)
          showNotification("Call functionality may be limited - connection error", "error")
        })
      } catch (error) {
        debugLog("Error initializing socket: " + error.message)
        showNotification("Call functionality is not available - socket error", "error")
      }
    }
  }

  // Add this new function to set up socket listeners
  function setupSocketListeners(socket) {
    // Incoming call
    socket.off("incoming_call") // Remove any existing listeners
    socket.on("incoming_call", (data) => {
      debugLog("Received incoming_call event", data)
      handleIncomingCall(data)
    })

    // Call accepted
    socket.off("call_accepted")
    socket.on("call_accepted", (data) => {
      debugLog("Received call_accepted event", data)
      handleCallAccepted(data)
    })

    // Call rejected
    socket.off("call_rejected")
    socket.on("call_rejected", (data) => {
      debugLog("Received call_rejected event", data)
      handleCallRejected(data)
    })

    // Call ended
    socket.off("call_ended")
    socket.on("call_ended", (data) => {
      debugLog("Received call_ended event", data)
      handleCallEnded()
    })

    // ICE candidate
    socket.off("ice_candidate")
    socket.on("ice_candidate", (data) => {
      debugLog("Received ice_candidate event", data)
      handleIceCandidate(data)
    })

    // Call request sent confirmation
    socket.off("call_request_sent")
    socket.on("call_request_sent", (data) => {
      debugLog("Call request sent confirmation received", data)
    })

    // Call error
    socket.off("call_error")
    socket.on("call_error", (data) => {
      debugLog("Call error received", data)
      showNotification(data.message || "Call error occurred", "error")
      endCall()
    })

    // Send a heartbeat to ensure the socket connection is active
    setInterval(() => {
      if (socket.connected) {
        const userId = getCurrentUserId()
        if (userId) {
          socket.emit("heartbeat", userId)
          debugLog("Sent heartbeat", userId)
        }
      } else {
        debugLog("Socket disconnected, attempting to reconnect")
        socket.connect()
      }
    }, 10000) // Every 10 seconds
  }

  // Start call timer
  function startCallTimer() {
    callDuration = 0
    callTimerElement.textContent = "00:00"

    callTimer = setInterval(() => {
      callDuration++
      callTimerElement.textContent = formatTime(callDuration)
    }, 1000)
  }

  // Stop call timer
  function stopCallTimer() {
    if (callTimer) {
      clearInterval(callTimer)
      callTimer = null
    }
  }

  // Start a call (audio or video)
  async function startCall(audioOnly = false) {
    try {
      console.log(`Starting ${audioOnly ? "audio" : "video"} call`)
      isAudioCall = audioOnly

      // Get the current chat user ID
      const chatHeader = document.querySelector(".chat-header-info h2")
      if (!chatHeader) {
        showNotification("No active chat selected", "error")
        return
      }

      // Get the current active chat
      if (window.activeChat && window.activeChat.id) {
        currentCallUser = window.activeChat.id
      } else {
        // Try to get from URL
        const url = window.location.pathname
        const matches = url.match(/\/chat\/direct\/(\d+)/)
        if (matches) {
          currentCallUser = matches[1]
        } else {
          showNotification("Cannot determine call recipient", "error")
          return
        }
      }

      console.log(`Call recipient: ${currentCallUser}`)

      // Show calling dialog
      createCallingDialog(currentCallUser)

      // Initialize call UI
      initCallUI()

      // Get local media stream
      const constraints = {
        audio: true,
        video: !audioOnly,
      }

      console.log("Requesting user media with constraints:", constraints)
      localStream = await navigator.mediaDevices.getUserMedia(constraints)
      console.log("Got local media stream")

      // Display local video
      localVideo.srcObject = localStream

      // Create peer connection
      peerConnection = new RTCPeerConnection(iceServers)
      console.log("Created peer connection")

      // Add local stream to peer connection
      localStream.getTracks().forEach((track) => {
        peerConnection.addTrack(track, localStream)
      })

      // Set up event handlers for peer connection
      peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
          console.log("Got ICE candidate:", event.candidate)
          // Send ICE candidate to remote peer
          window.socket.emit("ice_candidate", {
            recipient: currentCallUser,
            candidate: event.candidate,
          })
        }
      }

      peerConnection.ontrack = (event) => {
        console.log("Got remote track:", event.track)
        // Got remote stream
        remoteStream = event.streams[0]
        remoteVideo.srcObject = remoteStream
      }

      // Create offer
      console.log("Creating offer")
      const offer = await peerConnection.createOffer()
      await peerConnection.setLocalDescription(offer)
      console.log("Set local description")

      // Send offer to remote peer
      console.log("Sending call request to recipient:", currentCallUser)
      window.socket.emit("call_request", {
        recipient: currentCallUser,
        sdp: peerConnection.localDescription,
        isAudioCall: audioOnly,
      })

      callInProgress = true

      // Update UI for audio-only calls
      if (audioOnly) {
        videoBtn.disabled = true
        videoBtn.classList.add("disabled")
        document.querySelector(".audio-only-container").classList.remove("hidden")
        document.querySelector(".remote-video").classList.add("hidden")
        document.querySelector(".local-video-container").classList.add("hidden")
      }
    } catch (error) {
      console.error("Error starting call:", error)
      showNotification("Failed to start call: " + error.message, "error")
      if (callingDialog) {
        document.body.removeChild(callingDialog)
        callingDialog = null
      }
      endCall()
    }
  }

  // Update the handleIncomingCall function to include the caller's avatar
  function handleIncomingCall(data) {
    debugLog("Processing incoming call", data)

    // Check if there's already an incoming call dialog
    const existingDialog = document.querySelector(".incoming-call-dialog")
    if (existingDialog) {
      debugLog("Incoming call dialog already exists, removing it")
      existingDialog.remove()
    }

    // Show incoming call notification
    const caller = data.caller
    const isAudioOnly = data.isAudioCall

    // Get caller avatar - use default if not available
    const callerAvatar = caller.avatar ? `public/uploads/avatars/${caller.avatar}` : "public/uploads/avatars/default-avatar.png"

    debugLog(`Incoming ${isAudioOnly ? "audio" : "video"} call from ${caller.username || "Unknown"} (ID: ${caller.id})`)

    // Create incoming call dialog
    const incomingCallDialog = document.createElement("div")
    incomingCallDialog.className = "incoming-call-dialog"
    incomingCallDialog.innerHTML = `
    <div class="incoming-call-content">
      <div class="incoming-call-avatar">
        <img src="${callerAvatar}" alt="${caller.username || "Unknown"}" class="caller-avatar-img">
      </div>
      <div class="incoming-call-name">${caller.username || "Unknown"}</div>
      <div class="incoming-call-type">${isAudioOnly ? "Audio" : "Video"} Call</div>
      <div class="incoming-call-actions">
        <button id="reject-call-btn" class="incoming-call-btn reject">
          <i class="fas fa-phone-slash"></i>
        </button>
        <button id="accept-call-btn" class="incoming-call-btn accept">
          <i class="fas fa-phone"></i>
        </button>
      </div>
    </div>
  `

    document.body.appendChild(incomingCallDialog)
    debugLog("Incoming call dialog added to DOM")

    // Play ringtone
    try {
      if (ringtone) {
        ringtone.currentTime = 0
        ringtone.play().catch((e) => console.error("Could not play ringtone:", e))
        debugLog("Playing ringtone")
      } else {
        debugLog("Ringtone not initialized")
      }
    } catch (e) {
      console.error("Error playing ringtone:", e)
    }

    // Add event listeners
    document.getElementById("accept-call-btn").addEventListener("click", async () => {
      debugLog("Call accepted by user")
      // Stop ringtone
      if (ringtone) {
        ringtone.pause()
        ringtone.currentTime = 0
      }

      // Remove dialog
      incomingCallDialog.remove()

      // Accept call
      await acceptCall(data)
    })

    document.getElementById("reject-call-btn").addEventListener("click", () => {
      debugLog("Call rejected by user")
      // Stop ringtone
      if (ringtone) {
        ringtone.pause()
        ringtone.currentTime = 0
      }

      // Remove dialog
      incomingCallDialog.remove()

      // Reject call
      rejectCall(data)
    })

    // Auto-reject after 30 seconds
    setTimeout(() => {
      if (document.body.contains(incomingCallDialog)) {
        debugLog("Auto-rejecting call after timeout")
        // Stop ringtone
        if (ringtone) {
          ringtone.pause()
          ringtone.currentTime = 0
        }

        // Remove dialog
        incomingCallDialog.remove()

        // Reject call
        rejectCall(data)
      }
    }, 30000)
  }

  // Update the acceptCall function to use the caller's name and avatar
  async function acceptCall(data) {
    try {
      console.log("Accepting call with data:", data)
      isAudioCall = data.isAudioCall
      currentCallUser = data.caller.id

      // Initialize call UI
      initCallUI()

      // Show call modal
      callModal.classList.remove("hidden")

      // Update call status
      callStatus.textContent = "Connecting..."

      // Set caller name and avatar if available
      if (data.caller) {
        const callerName = data.caller.username || "Unknown"
        document.getElementById("caller-name").textContent = callerName

        // Update avatar if available
        if (data.caller.avatar) {
          const avatarContainer = document.querySelector(".audio-only-avatar")
          if (avatarContainer) {
            avatarContainer.innerHTML = `<img src="public/uploads/avatars/${data.caller.avatar}" alt="${callerName}" class="caller-avatar-img">`
          }
        }
      }

      // Get local media stream
      const constraints = {
        audio: true,
        video: !isAudioCall,
      }

      console.log("Requesting user media with constraints:", constraints)
      localStream = await navigator.mediaDevices.getUserMedia(constraints)
      console.log("Got local media stream")

      // Display local video
      localVideo.srcObject = localStream

      // Create peer connection
      peerConnection = new RTCPeerConnection(iceServers)
      console.log("Created peer connection")

      // Add local stream to peer connection
      localStream.getTracks().forEach((track) => {
        peerConnection.addTrack(track, localStream)
      })

      // Set up event handlers for peer connection
      peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
          console.log("Got ICE candidate:", event.candidate)
          // Send ICE candidate to remote peer
          window.socket.emit("ice_candidate", {
            recipient: currentCallUser,
            candidate: event.candidate,
          })
        }
      }

      peerConnection.ontrack = (event) => {
        console.log("Got remote track:", event.track)
        // Got remote stream
        remoteStream = event.streams[0]
        remoteVideo.srcObject = remoteStream
      }

      // Set remote description
      console.log("Setting remote description from offer")
      await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp))

      // Create answer
      console.log("Creating answer")
      const answer = await peerConnection.createAnswer()
      await peerConnection.setLocalDescription(answer)
      console.log("Set local description (answer)")

      // Send answer to remote peer
      console.log("Sending call_accepted to caller:", currentCallUser)
      window.socket.emit("call_accepted", {
        recipient: currentCallUser,
        sdp: peerConnection.localDescription,
      })

      callInProgress = true

      // Start call timer
      startCallTimer()

      // Update UI for audio-only calls
      if (isAudioCall) {
        videoBtn.disabled = true
        videoBtn.classList.add("disabled")
        document.querySelector(".audio-only-container").classList.remove("hidden")
        document.querySelector(".remote-video").classList.add("hidden")
        document.querySelector(".local-video-container").classList.add("hidden")
      }
    } catch (error) {
      console.error("Error accepting call:", error)
      showNotification("Failed to accept call: " + error.message, "error")
      endCall()
    }
  }

  // Reject incoming call
  function rejectCall(data) {
    console.log("Rejecting call from:", data.caller.id)
    window.socket.emit("call_rejected", {
      recipient: data.caller.id,
    })
  }

  // Handle call accepted
  async function handleCallAccepted(data) {
    try {
      console.log("Call was accepted, received answer:", data)

      // Remove calling dialog
      if (callingDialog) {
        document.body.removeChild(callingDialog)
        callingDialog = null
      }

      // Show call modal
      callModal.classList.remove("hidden")

      // Update call status
      callStatus.textContent = "Connected"

      // Start call timer
      startCallTimer()

      // Set remote description
      console.log("Setting remote description from answer")
      await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp))
      console.log("Remote description set successfully")
    } catch (error) {
      console.error("Error handling call accepted:", error)
      showNotification("Error establishing connection", "error")
      endCall()
    }
  }

  // Handle call rejected
  function handleCallRejected(data) {
    console.log("Call was rejected:", data)
    showNotification("Call was rejected", "info")

    // Remove calling dialog
    if (callingDialog) {
      document.body.removeChild(callingDialog)
      callingDialog = null
    }

    endCall()
  }

  // Handle call ended
  function handleCallEnded() {
    console.log("Call was ended by the other party")
    showNotification("Call ended", "info")

    // Remove calling dialog if it exists
    if (callingDialog) {
      document.body.removeChild(callingDialog)
      callingDialog = null
    }

    endCall()
  }

  // Handle ICE candidate
  async function handleIceCandidate(data) {
    try {
      console.log("Received ICE candidate:", data.candidate)
      if (peerConnection && data.candidate) {
        await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate))
        console.log("Added ICE candidate successfully")
      }
    } catch (error) {
      console.error("Error adding ICE candidate:", error)
    }
  }

  // Toggle audio
  function toggleAudio() {
    if (localStream) {
      const audioTrack = localStream.getAudioTracks()[0]
      if (audioTrack) {
        audioTrack.enabled = !audioTrack.enabled
        isMuted = !audioTrack.enabled
        console.log("Audio track enabled:", audioTrack.enabled)

        // Update button icon and class
        if (isMuted) {
          muteBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>'
          muteBtn.title = "Unmute"
          muteBtn.classList.add("active")
        } else {
          muteBtn.innerHTML = '<i class="fas fa-microphone"></i>'
          muteBtn.title = "Mute"
          muteBtn.classList.remove("active")
        }
      }
    }
  }

  // Toggle video
  function toggleVideo() {
    if (localStream && !isAudioCall) {
      const videoTrack = localStream.getVideoTracks()[0]
      if (videoTrack) {
        videoTrack.enabled = !videoTrack.enabled
        isVideoOff = !videoTrack.enabled
        console.log("Video track enabled:", videoTrack.enabled)

        // Update button icon and class
        if (isVideoOff) {
          videoBtn.innerHTML = '<i class="fas fa-video-slash"></i>'
          videoBtn.title = "Turn on video"
          videoBtn.classList.add("active")
        } else {
          videoBtn.innerHTML = '<i class="fas fa-video"></i>'
          videoBtn.title = "Turn off video"
          videoBtn.classList.remove("active")
        }
      }
    }
  }

  // End call
  function endCall() {
    console.log("Ending call")

    // Send call ended event if call was in progress
    if (callInProgress && currentCallUser) {
      console.log("Sending call_ended event to:", currentCallUser)
      window.socket.emit("call_ended", {
        recipient: currentCallUser,
      })
    }

    // Stop call timer
    stopCallTimer()

    // Stop all tracks in local stream
    if (localStream) {
      localStream.getTracks().forEach((track) => {
        console.log("Stopping track:", track.kind)
        track.stop()
      })
      localStream = null
    }

    // Close peer connection
    if (peerConnection) {
      peerConnection.close()
      peerConnection = null
      console.log("Peer connection closed")
    }

    // Reset remote stream
    remoteStream = null

    // Hide call modal
    if (callModal) {
      callModal.classList.add("hidden")
    }

    // Reset video elements
    if (localVideo) localVideo.srcObject = null
    if (remoteVideo) remoteVideo.srcObject = null

    // Reset call state
    callInProgress = false
    currentCallUser = null
    isMuted = false
    isVideoOff = false

    // Reset UI
    if (document.querySelector(".audio-only-container")) {
      document.querySelector(".audio-only-container").classList.add("hidden")
    }
    if (document.querySelector(".remote-video")) {
      document.querySelector(".remote-video").classList.remove("hidden")
    }
    if (document.querySelector(".local-video-container")) {
      document.querySelector(".local-video-container").classList.remove("hidden")
    }
    if (videoBtn) {
      videoBtn.disabled = false
      videoBtn.classList.remove("disabled")
      videoBtn.classList.remove("active")
      videoBtn.innerHTML = '<i class="fas fa-video"></i>'
    }
    if (muteBtn) {
      muteBtn.classList.remove("active")
      muteBtn.innerHTML = '<i class="fas fa-microphone"></i>'
    }

    console.log("Call ended successfully")
  }

  // Show notification
  function showNotification(message, type) {
    console.log(`Notification: ${type} - ${message}`)
    if (window.showNotification) {
      window.showNotification(message, type)
    } else {
      // Fallback notification
      console.log(`${type}: ${message}`)
      alert(message)
    }
  }

  // Initialize WebRTC when DOM is loaded
  initWebRTC()

  // Make functions available globally
  window.webrtcHandler = {
    startCall,
    endCall,
    toggleAudio,
    toggleVideo,
  }
})

// Import socket.io client
const io = window.io

