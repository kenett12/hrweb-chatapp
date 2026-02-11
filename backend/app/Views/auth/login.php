<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRWeb - Chat App</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-hover: #0b5ed7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            /* Updated Background Image Logic */
            background: url('<?= base_url("uploads/bg/login.png") ?>') no-repeat center center fixed;
            background-size: cover; /* Ensures image covers the whole screen */
            background-color: #f8f9fa; /* Fallback color */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 20px;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3); 
            z-index: -1;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 48px 40px;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: slideUp 0.5s ease-out;
            margin-bottom: 5vh; 
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #0d6efd, #0dcaf0);
        }
        
        .app-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .app-logo img {
            max-width: 100%;
            height: auto;
            margin-bottom: 12px;
        }
        
        .app-logo p {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .form-floating > .form-control {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            height: 52px;
            font-size: 15px;
            background-color: #fff;
        }
        
        .form-floating > .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
        }

        .form-floating > label {
            padding-top: 14px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 8px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            position: relative;
            padding-left: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #c92a2a;
        }
        
        .alert-danger::before {
            background-color: #ff6b6b;
        }
        
        .alert-success {
            background-color: #ebfbee;
            color: #2b8a3e;
        }
        
        .alert-success::before {
            background-color: #51cf66;
        }
        
        .alert-icon {
            margin-right: 12px;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        form {
            animation: fadeIn 0.4s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="app-logo">
            <img src="<?= base_url('uploads/logo/cropped.png') ?>" alt="Logo" width="300">
            <p>Connect with customer support real-time</p>
        </div>
        
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger">
                <div class="alert-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                </div>
                <div><?= session('error') ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (session()->has('success')): ?>
            <div class="alert alert-success">
                <div class="alert-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <div><?= session('success') ?></div>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('login') ?>" method="post">
            <?= csrf_field() ?>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" placeholder="Username or Email" required>
                <label for="username">Username or Email</label>
            </div>
            
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
        </form>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>