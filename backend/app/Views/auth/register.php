<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Chat App</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            background-color: #f8f9fa;
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden; /* Prevent body scroll */
        }
        
        .register-container {
            max-width: 450px;
            width: 100%;
            padding: 25px 30px;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            position: relative;
            border: 1px solid rgba(0,0,0,0.02);
            animation: slideUp 0.5s ease-out;
            max-height: 98vh; /* Ensure it never exceeds viewport */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .register-container::before {
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
            margin-bottom: 15px;
        }
        
        .app-logo img {
            max-width: 100%;
            height: auto;
            margin-bottom: 5px;
        }
        
        .app-logo p {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0;
        }

        /* Compact Avatar Styling */
        .avatar-upload-wrapper {
            position: relative;
            width: 85px;
            height: 85px;
            margin: 0 auto 15px;
        }
        
        .avatar-preview { 
            width: 100%; 
            height: 100%; 
            border-radius: 50%; 
            object-fit: cover; 
            display: block; 
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(13, 110, 253, 0.8);
            backdrop-filter: blur(2px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            cursor: pointer;
            color: white;
            border: 3px solid #fff;
        }
        
        .avatar-upload-wrapper:hover .avatar-upload-overlay {
            opacity: 1;
        }
        
        .avatar-upload-overlay svg {
            width: 20px;
            height: 20px;
            margin-bottom: 2px;
        }
        
        .avatar-upload-overlay span {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        #avatar {
            display: none;
        }

        /* Compact Form Styling */
        .form-floating {
            margin-bottom: 12px;
        }

        .form-floating > .form-control {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            height: 48px; /* Compact height */
            font-size: 14px;
            min-height: 48px;
        }
        
        .form-floating > .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .form-floating > label {
            padding-top: 12px;
            padding-bottom: 12px;
            color: #6c757d;
            font-size: 13px;
        }

        /* Adjust floating label position for compact height */
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transform: scale(0.85) translateY(-0.7rem) translateX(0.15rem);
        }

        /* Password Toggle */
        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #adb5bd;
            transition: color 0.2s;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .form-control.with-icon {
            padding-right: 40px;
        }

        /* Password Requirements Box */
        .password-requirements {
            display: none;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 8px;
            font-size: 12px;
            animation: fadeIn 0.3s ease;
        }

        .password-requirements.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .password-requirements h6 {
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            color: #6c757d;
            transition: color 0.2s;
        }

        .requirement-item:last-child {
            margin-bottom: 0;
        }

        .requirement-item i {
            margin-right: 8px;
            font-size: 14px;
            transition: color 0.2s;
        }

        .requirement-item.met {
            color: #198754;
        }

        .requirement-item.met i {
            color: #198754;
        }

        .requirement-item.unmet i {
            color: #dc3545;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 5px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        
        /* Compact Alert */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            position: relative;
            padding-left: 15px;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #c92a2a;
        }
        .alert-danger::before { background-color: #ff6b6b; }
        
        .alert ul { padding-left: 15px; margin-bottom: 0; }
        .alert li { margin-bottom: 2px; }
        
        .mt-compact {
            margin-top: 15px;
        }
        
        .mt-compact p {
            font-size: 13px;
            color: #6c757d;
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        a:hover {
            text-decoration: underline;
        }

        /* Screen Height optimizations */
        @media (max-height: 700px) {
            .register-container {
                padding: 15px 25px;
            }
            .app-logo { margin-bottom: 10px; }
            .app-logo img { width: 140px; }
            .avatar-upload-wrapper { width: 70px; height: 70px; margin-bottom: 10px; }
            .form-floating { margin-bottom: 8px; }
            .form-floating > .form-control { height: 42px; min-height: 42px; font-size: 13px; }
            .form-floating > label { padding-top: 10px; font-size: 12px; }
            .btn-primary { padding: 8px; font-size: 14px; }
            .mt-compact { margin-top: 10px; }
            .password-requirements { padding: 10px 12px; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="app-logo">
            <img src="<?= base_url('uploads/logo/cropped.png') ?>" alt="Logo" width="160">
            <p class="text-muted">Create your account</p>
        </div>
        
        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('register') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <div class="text-center">
                <div class="avatar-upload-wrapper">
                    <img src="<?= base_url('public/uploads/avatars/default-avatar.png') ?>" alt="Avatar Preview" class="avatar-preview" id="avatar-preview">
                    <label for="avatar" class="avatar-upload-overlay">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                            <path d="M2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2zm.5 2a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm9 2.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0z"/>
                        </svg>
                        <span>UPLOAD</span>
                    </label>
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                </div>
            </div>
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="Email address" required>
                <label for="email">Email</label>
            </div>
            
            <div class="form-floating password-container">
                <input type="password" class="form-control with-icon" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
                <span class="password-toggle" onclick="togglePassword('password', this)">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
            
            <div class="password-requirements" id="password-requirements">
                <h6>Password Requirements:</h6>
                <div class="requirement-item" id="req-length">
                    <i class="bi bi-circle"></i>
                    <span>At least 8 characters long</span>
                </div>
                <div class="requirement-item" id="req-complexity">
                    <i class="bi bi-circle"></i>
                    <span>Contains uppercase, number, and special character (@$!%*?&)</span>
                </div>
            </div>
            
            <div class="form-floating password-container" style="margin-bottom: 15px;">
                <input type="password" class="form-control with-icon" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm_password">Confirm Password</label>
                <span class="password-toggle" onclick="togglePassword('confirm_password', this)">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
        
        <div class="mt-compact text-center">
            <p>Already have an account? <a href="<?= base_url('login') ?>">Sign In</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, toggleElement) {
            const passwordInput = document.getElementById(inputId);
            const icon = toggleElement.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { 
                    document.getElementById('avatar-preview').src = e.target.result; 
                }
                reader.readAsDataURL(file);
            }
        });

        // Password requirements functionality
        const passwordInput = document.getElementById('password');
        const requirementsBox = document.getElementById('password-requirements');
        const lengthReq = document.getElementById('req-length');
        const complexityReq = document.getElementById('req-complexity');

        // Show requirements box when password field is focused
        passwordInput.addEventListener('focus', function() {
            requirementsBox.classList.add('show');
        });

        // Hide requirements box when password field loses focus
        passwordInput.addEventListener('blur', function(e) {
            // Small delay to allow clicking within the requirements box
            setTimeout(function() {
                if (document.activeElement !== passwordInput) {
                    requirementsBox.classList.remove('show');
                }
            }, 150);
        });

        // Check password requirements on input
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Check length requirement (at least 8 characters)
            const lengthMet = password.length >= 8;
            updateRequirement(lengthReq, lengthMet);
            
            // Check complexity requirement (uppercase, number, and special character)
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[@$!%*?&]/.test(password);
            const complexityMet = hasUppercase && hasNumber && hasSpecial;
            updateRequirement(complexityReq, complexityMet);
        });

        function updateRequirement(element, isMet) {
            const icon = element.querySelector('i');
            
            if (isMet) {
                element.classList.add('met');
                element.classList.remove('unmet');
                icon.classList.remove('bi-circle', 'bi-x-circle-fill');
                icon.classList.add('bi-check-circle-fill');
            } else {
                element.classList.remove('met');
                element.classList.add('unmet');
                icon.classList.remove('bi-circle', 'bi-check-circle-fill');
                icon.classList.add('bi-x-circle-fill');
            }
        }
    </script>
</body>
</html>