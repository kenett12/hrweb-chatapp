(function() {
    // 1. GLOBAL PATH CONFIGURATION
    const APP_ROOT = '/chat-app/backend';

    // 2. AUDIO 404 FIX
    const originalAudio = window.Audio;
    window.Audio = function(url) {
        if (typeof url === 'string' && (url.startsWith('/assets/') || url.startsWith('assets/'))) {
            const cleanUrl = url.startsWith('/') ? url.substring(1) : url;
            const fixedUrl = `${APP_ROOT}/${cleanUrl}`;
            return new originalAudio(fixedUrl);
        }
        return new originalAudio(url);
    };

    // 3. SOCKET RACE CONDITION FIX
    if (typeof window.socket === 'undefined') {
        const commandQueue = [];
        const socketProxy = {
            _isProxy: true,
            on: function(event, callback) {
                commandQueue.push({ type: 'on', args: [event, callback] });
            },
            emit: function(event, data) {
                commandQueue.push({ type: 'emit', args: [event, data] });
            },
            off: function(event, callback) {
                 commandQueue.push({ type: 'off', args: [event, callback] });
            },
            connected: false,
            id: null
        };

        Object.defineProperty(window, 'socket', {
            configurable: true,
            get: function() {
                return socketProxy;
            },
            set: function(realSocket) {
                console.log(`[SystemPatch] Real socket initialized.`);
                Object.defineProperty(window, 'socket', {
                    value: realSocket,
                    writable: true,
                    configurable: true
                });
                commandQueue.forEach(cmd => {
                    if (cmd.type === 'on') realSocket.on(...cmd.args);
                    if (cmd.type === 'emit') realSocket.emit(...cmd.args);
                    if (cmd.type === 'off') realSocket.off(...cmd.args);
                });
            }
        });
    }

    // 4. PREVENT NULL ELEMENT CRASHES
    document.addEventListener('DOMContentLoaded', () => {
        const requiredIds = ['emoji-picker-container', 'emoji-grid', 'chat-messages'];
        requiredIds.forEach(id => {
            if (!document.getElementById(id)) {
                const dummy = document.createElement('div');
                dummy.id = id;
                dummy.style.display = 'none';
                document.body.appendChild(dummy);
            }
        });
    });

    // 5. API PATH HELPER
    window.getApiUrl = (endpoint) => {
        const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
        return `${APP_ROOT}/api/${cleanEndpoint}`;
    };
})();