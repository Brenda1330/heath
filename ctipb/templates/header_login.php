<?php
// templates/header_login.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Login Panel'; ?></title>
  <style>
:root {
            --primary-color: #3B82F6;
            --primary-hover: #2563EB;
            --main-bg: #F9FAFB;
            --card-bg: #FFFFFF;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .login-wrapper { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            min-height: 100vh;
            width: 100%;
        }
        
        .login-graphic {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
            color: white; 
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            align-items: center; 
            padding: 3rem; 
            text-align: center;
            animation: slideInLeft 0.8s ease-out forwards;
            position: relative;
            overflow: hidden;
        }
        
        .login-graphic::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }
        
        @keyframes slideInLeft { 
            from { 
                transform: translateX(-100%); 
                opacity: 0;
            } 
            to { 
                transform: translateX(0); 
                opacity: 1;
            } 
        }
        
        .login-graphic .icon { 
            font-size: 5rem; 
            margin-bottom: 1.5rem; 
            opacity: 0.9;
            position: relative;
            z-index: 2;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-graphic h1 { 
            font-weight: 700; 
            font-size: 2.5rem; 
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .login-graphic p { 
            font-size: 1.1rem; 
            max-width: 400px; 
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }
        
        .login-form-container {
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 2rem;
            animation: fadeInUp 1s 0.3s ease-out forwards; 
            opacity: 0;
            transform: translateY(30px);
            position: relative;
        }
        
        @keyframes fadeInUp { 
            from { 
                opacity: 0;
                transform: translateY(30px);
            } 
            to { 
                opacity: 1;
                transform: translateY(0);
            } 
        }
        
        .login-card { 
            width: 100%; 
            max-width: 420px;
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 10;
        }
        
        .login-card h3 { 
            font-weight: 700; 
            font-size: 1.75rem; 
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .login-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        /* COMPLETELY CUSTOM Form Styles - No Bootstrap interference */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .custom-input {
            border: 1.5px solid var(--border-color); 
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            height: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--card-bg);
            width: 100%;
            font-family: 'Inter', sans-serif;
            display: block;
        }
        
        .custom-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: var(--card-bg);
            outline: none;
        }
        
        .custom-input.error {
            border-color: var(--danger-color);
        }
        
        /* FIXED: Password wrapper with SINGLE eye icon */
        .password-container {
            position: relative;
            width: 100%;
        }
        
        .password-input {
            border: 1.5px solid var(--border-color); 
            border-radius: 0.75rem;
            padding: 0.75rem 3rem 0.75rem 1rem;
            height: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--card-bg);
            width: 100%;
            font-family: 'Inter', sans-serif;
            padding-right: 3rem !important; /* Force padding for eye icon */
        }
        
        .password-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: var(--card-bg);
            outline: none;
        }
        
        .password-input.error {
            border-color: var(--danger-color);
        }
        
        /* SINGLE Eye Icon - Completely isolated from Bootstrap */
        .eye-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            z-index: 10;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .eye-toggle-btn:hover {
            color: var(--primary-color);
            background: rgba(59, 130, 246, 0.1);
        }
        
        .eye-toggle-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Remove any Bootstrap form-control styling */
        .form-control {
            all: unset;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            color: white;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert { 
            border-radius: 0.75rem;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .error-message {
            color: var(--danger-color);
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        /* Loading state */
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-loading .btn-text {
            visibility: hidden;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .login-wrapper { 
                grid-template-columns: 1fr;
            }
            
            .login-graphic { 
                display: none; 
            }
            
            .login-form-container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 2rem;
                margin: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem;
                margin: 0.5rem;
            }
            
            .login-card h3 {
                font-size: 1.5rem;
            }
        }
        </style>
</head>
<body>