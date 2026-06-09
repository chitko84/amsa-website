<?php
$authInitialState = isset($authInitialState) && $authInitialState === 'register' ? 'register' : 'login';
$loginEmailValue = isset($postedEmail) ? htmlspecialchars($postedEmail, ENT_QUOTES, 'UTF-8') : '';
$registerNameValue = function_exists('oldValue') ? oldValue('name') : htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
$registerEmailValue = function_exists('oldValue') ? oldValue('email') : htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
$authIsLogin = $authInitialState === 'login';
$authIsRegister = !$authIsLogin;
?>

<style>
    :root {
        --amsa-primary: #8f3b3b;
        --amsa-primary-dark: #6f2929;
        --amsa-gold: #d4af37;
        --amsa-cream: #fff9f0;
        --amsa-text: #2f2f2f;
        --amsa-muted: #756b66;
        --amsa-panel-shadow: 0 30px 90px rgba(42, 12, 12, 0.34);
    }

    * {
        box-sizing: border-box;
    }

    body.points-page {
        min-height: 100vh;
        margin: 0;
        font-family: "Poppins", "Segoe UI", Arial, sans-serif;
        color: var(--amsa-text);
        background:
            radial-gradient(circle at 15% 15%, rgba(212, 175, 55, 0.22), transparent 28%),
            linear-gradient(135deg, #7b3434 0%, #9c4b45 48%, #793333 100%);
        overflow-x: hidden;
    }

    .auth-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .auth-container {
        width: min(1160px, 100%);
        min-height: 760px;
        position: relative;
        border-radius: 32px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: var(--amsa-panel-shadow);
        border: 1px solid rgba(255, 255, 255, 0.88);
        isolation: isolate;
    }

    .auth-panel {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 50%;
        overflow: hidden;
        transition:
            transform 760ms cubic-bezier(0.22, 1, 0.36, 1),
            opacity 760ms cubic-bezier(0.22, 1, 0.36, 1),
            box-shadow 760ms cubic-bezier(0.22, 1, 0.36, 1),
            filter 760ms cubic-bezier(0.22, 1, 0.36, 1);
        will-change: transform, opacity;
    }

    .auth-panel--form {
        left: 0;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.98)),
            var(--amsa-cream);
        padding: 40px 42px;
        display: flex;
        align-items: center;
        z-index: 2;
    }

    .auth-panel--image {
        left: 50%;
        background: #381818;
        z-index: 1;
    }

    .auth-container.login-state .auth-panel--form {
        transform: translateX(0) scale(1);
    }

    .auth-container.login-state .auth-panel--image {
        transform: translateX(0) scale(1);
    }

    .auth-container.register-state .auth-panel--form {
        transform: translateX(100%) scale(0.985);
        z-index: 1;
    }

    .auth-container.register-state .auth-panel--image {
        transform: translateX(-100%) scale(0.985);
        z-index: 2;
    }

    .auth-panel-inner {
        width: 100%;
        max-width: 460px;
        margin: 0 auto;
    }

    .auth-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
    }

    .auth-logo {
        width: 74px;
        height: 74px;
        object-fit: contain;
        background: #ffffff;
        padding: 10px;
        border-radius: 22px;
        box-shadow: 0 14px 28px rgba(143, 59, 59, 0.16);
        border: 1px solid #f1ded8;
        flex: 0 0 auto;
    }

    .auth-brand-copy p {
        margin: 0;
        font-size: 0.8rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--amsa-muted);
        font-weight: 800;
    }

    .auth-brand-copy h1 {
        margin: 4px 0 0;
        font-size: 1.55rem;
        line-height: 1.1;
        color: var(--amsa-primary-dark);
        font-weight: 900;
        letter-spacing: -0.04em;
    }

    .auth-form-stage {
        position: relative;
        min-height: 600px;
    }

    .auth-form-view {
        position: absolute;
        inset: 0;
        opacity: 0;
        transform: translateX(26px) scale(0.985);
        pointer-events: none;
        transition:
            transform 640ms cubic-bezier(0.22, 1, 0.36, 1),
            opacity 640ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .auth-form-view.is-active {
        opacity: 1;
        transform: translateX(0) scale(1);
        pointer-events: auto;
    }

    .auth-form-title {
        margin-bottom: 16px;
    }

    .auth-form-title h2 {
        margin: 0 0 8px;
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 900;
        letter-spacing: -0.05em;
        color: #2f1c1c;
    }

    .auth-form-title p {
        margin: 0;
        color: var(--amsa-muted);
        line-height: 1.55;
        font-size: 0.95rem;
    }

    .auth-intro {
        background: linear-gradient(135deg, #fff8e1, #ffffff);
        border-left: 5px solid var(--amsa-gold);
        padding: 14px 16px;
        border-radius: 16px;
        font-size: 0.92rem;
        line-height: 1.58;
        color: #54463d;
        margin-bottom: 22px;
        border-top: 1px solid rgba(212, 175, 55, 0.22);
        border-right: 1px solid rgba(212, 175, 55, 0.22);
        border-bottom: 1px solid rgba(212, 175, 55, 0.22);
    }

    .form-label {
        font-weight: 700;
        font-size: 0.9rem;
        color: #2f2f2f;
        margin-bottom: 8px;
    }

    .amsa-form-control {
        height: 50px;
        width: 100%;
        border-radius: 14px;
        border: 1px solid #eadbd6;
        background: #fffdfb;
        padding: 12px 15px;
        font-size: 0.95rem;
        color: var(--amsa-text);
        transition: all 0.22s ease;
    }

    .amsa-form-control:focus {
        background: #ffffff;
        border-color: var(--amsa-gold);
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.18);
        outline: none;
    }

    .amsa-upload-hint {
        display: block;
        margin-top: 6px;
        color: #857a74;
        font-size: 0.8rem;
    }

    .amsa-btn-primary {
        height: 50px;
        border: none;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--amsa-primary), var(--amsa-primary-dark));
        color: #ffffff;
        font-weight: 800;
        box-shadow: 0 16px 30px rgba(143, 59, 59, 0.28);
        transition: transform 0.22s ease, box-shadow 0.22s ease, background 0.22s ease;
        margin-top: 4px;
    }

    .amsa-btn-primary:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, var(--amsa-primary-dark), var(--amsa-primary));
        box-shadow: 0 20px 35px rgba(143, 59, 59, 0.34);
    }

    .auth-links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-align: center;
        margin-top: 22px;
    }

    .auth-links p {
        color: #655d59;
        margin: 0;
        font-size: 0.92rem;
    }

    .auth-link {
        color: var(--amsa-primary-dark);
        font-weight: 800;
        text-decoration: none;
        border: none;
        background: transparent;
        padding: 0;
        transition: color 0.22s ease, transform 0.22s ease;
    }

    .auth-link:hover {
        color: var(--amsa-gold);
        transform: translateY(-1px);
    }

    .auth-home-btn,
    .auth-switch-btn {
        border-radius: 14px;
        padding: 10px 18px;
        color: var(--amsa-primary-dark);
        border: 1px solid rgba(143, 59, 59, 0.22);
        background: #ffffff;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.22s ease, background 0.22s ease, color 0.22s ease, box-shadow 0.22s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
    }

    .auth-home-btn:hover,
    .auth-switch-btn:hover {
        transform: translateY(-2px);
        background: var(--amsa-primary-dark);
        color: #ffffff;
        box-shadow: 0 14px 24px rgba(143, 59, 59, 0.2);
    }

    .amsa-alert {
        border: none;
        border-radius: 14px;
        padding: 13px 15px;
        font-size: 0.9rem;
        margin-bottom: 18px;
    }

    .auth-image-stage {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .auth-image-stage img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .auth-image-stage::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(25, 10, 10, 0.08), rgba(95, 24, 24, 0.66)),
            linear-gradient(90deg, rgba(255, 255, 255, 0.16), transparent 36%);
        z-index: 1;
    }

    .auth-image-stage::after {
        content: "";
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 26px;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.38), rgba(255, 255, 255, 0));
        opacity: 0.55;
        z-index: 2;
        transform: translateX(0);
        transition: transform 760ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .auth-container.register-state .auth-image-stage::after {
        transform: translateX(460px);
    }

    .auth-image-caption {
        position: absolute;
        left: 34px;
        right: 34px;
        bottom: 34px;
        z-index: 3;
        color: #ffffff;
        padding: 24px;
        border-radius: 24px;
        background: rgba(120, 55, 55, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.26);
        backdrop-filter: blur(12px);
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        transition: opacity 640ms cubic-bezier(0.22, 1, 0.36, 1), transform 640ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .auth-image-caption span {
        display: block;
        color: #ffe27a;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.84rem;
        margin-bottom: 10px;
    }

    .auth-image-caption strong {
        display: block;
        font-size: 1.32rem;
        line-height: 1.4;
    }

    .auth-image-caption--login,
    .auth-image-caption--register {
        opacity: 0;
        transform: translateX(18px);
        pointer-events: none;
    }

    .auth-container.login-state .auth-image-caption--login {
        opacity: 1;
        transform: translateX(0);
        pointer-events: auto;
    }

    .auth-container.register-state .auth-image-caption--register {
        opacity: 1;
        transform: translateX(0);
        pointer-events: auto;
    }

    .auth-message {
        margin-bottom: 18px;
    }

    @media (max-width: 960px) {
        .auth-shell {
            padding: 14px;
        }

        .auth-container {
            min-height: auto;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
        }

        .auth-panel {
            position: relative;
            left: auto;
            top: auto;
            bottom: auto;
            width: 100%;
            transform: none !important;
        }

        .auth-panel--image {
            order: 0;
            min-height: 260px;
        }

        .auth-panel--form {
            order: 1;
            padding: 28px 22px 30px;
        }

        .auth-form-stage {
            min-height: auto;
        }

        .auth-form-view {
            position: relative;
            inset: auto;
            transform: none;
            opacity: 0;
            pointer-events: none;
            display: none;
        }

        .auth-form-view.is-active {
            opacity: 1;
            pointer-events: auto;
            display: block;
        }

        .auth-image-caption {
            left: 20px;
            right: 20px;
            bottom: 20px;
            padding: 18px;
        }

        .auth-image-caption strong {
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .auth-shell {
            padding: 10px;
        }

        .auth-panel--image {
            min-height: 220px;
        }

        .auth-brand {
            gap: 12px;
            margin-bottom: 20px;
        }

        .auth-logo {
            width: 64px;
            height: 64px;
        }

        .auth-brand-copy h1 {
            font-size: 1.28rem;
        }

        .auth-form-title h2 {
            font-size: 1.6rem;
        }

        .auth-image-caption {
            display: none;
        }
    }
</style>

<div class="auth-shell">
    <div class="auth-container <?php echo $authIsRegister ? 'register-state' : 'login-state'; ?>" id="authContainer" data-auth-state="<?php echo htmlspecialchars($authInitialState, ENT_QUOTES, 'UTF-8'); ?>">
        <section class="auth-panel auth-panel--form">
            <div class="auth-panel-inner">
                <div class="auth-brand">
                    <img src="../img/logo.png" class="auth-logo" alt="AMSA">
                    <div class="auth-brand-copy">
                        <p>AMSA Points</p>
                        <h1>Member access</h1>
                    </div>
                </div>

                <div class="auth-form-stage">
                    <div class="auth-form-view auth-form-view--login <?php echo $authIsLogin ? 'is-active' : ''; ?>" data-auth-view="login" aria-hidden="<?php echo $authIsLogin ? 'false' : 'true'; ?>">
                        <div class="auth-form-title">
                            <h2>Login to AMSA Points</h2>
                            <p>Access your activity points dashboard.</p>
                        </div>

                        <div class="auth-intro">
                            <strong>Welcome back.</strong> Track your approved activities, rankings, and AMSA participation history.
                        </div>

                        <?php if ($authIsLogin && !empty($error)): ?>
                            <div class="alert alert-danger amsa-alert auth-message">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <?php echo csrfInput(); ?>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control amsa-form-control"
                                    value="<?php echo $loginEmailValue; ?>"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control amsa-form-control"
                                    autocomplete="current-password"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn amsa-btn-primary w-100">Login</button>
                        </form>

                        <div class="auth-links">
                            <p>New member?</p>
                            <button type="button" class="auth-switch-btn" data-auth-toggle="register">Create an account</button>
                            <a class="auth-home-btn" href="../index.php">Back to Home</a>
                        </div>
                    </div>

                    <div class="auth-form-view auth-form-view--register <?php echo $authIsRegister ? 'is-active' : ''; ?>" data-auth-view="register" aria-hidden="<?php echo $authIsRegister ? 'false' : 'true'; ?>">
                        <div class="auth-form-title">
                            <h2>Register for AMSA Points</h2>
                            <p>Join the member activity and rewards system.</p>
                        </div>

                        <div class="auth-intro">
                            <strong>Start participating.</strong> Submit activities, upload evidence, and build your AMSA points record.
                        </div>

                        <?php if ($authIsRegister && !empty($error)): ?>
                            <div class="alert alert-danger amsa-alert auth-message">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($authIsRegister && !empty($success)): ?>
                            <div class="alert alert-success amsa-alert auth-message">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php">
                            <?php echo csrfInput(); ?>

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control amsa-form-control"
                                    value="<?php echo $registerNameValue; ?>"
                                    autocomplete="name"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control amsa-form-control"
                                    value="<?php echo $registerEmailValue; ?>"
                                    pattern="^[A-Za-z0-9._%+\-]+@([Ss][Tt][Uu][Dd][Ee][Nn][Tt]\.)?[Aa][Ii][Uu]\.[Ee][Dd][Uu]\.[Mm][Yy]$"
                                    title="Use your official AIU email ending with @aiu.edu.my or @student.aiu.edu.my."
                                    autocomplete="email"
                                    required
                                >
                                <small class="amsa-upload-hint">Use your official AIU email address.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control amsa-form-control"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >
                                <small class="amsa-upload-hint">Use at least 8 characters.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control amsa-form-control"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn amsa-btn-primary w-100">Register</button>
                        </form>

                        <div class="auth-links">
                            <p>Already have an account?</p>
                            <button type="button" class="auth-switch-btn" data-auth-toggle="login">Log in</button>
                            <a class="auth-home-btn" href="../index.php">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-panel auth-panel--image image-panel" aria-label="AIU campus">
            <div class="auth-image-stage">
                <img src="https://ace-sedi.aiu.edu.my/assets/images/aiu-campus-1.jpg" alt="AIU campus">

                <div class="auth-image-caption auth-image-caption--login">
                    <span>AMSA AIU</span>
                    <strong>Track participation, achievements, and activity points in one member portal.</strong>
                </div>

                <div class="auth-image-caption auth-image-caption--register">
                    <span>AMSA AIU</span>
                    <strong>Create your member account with an official AIU email to start earning points.</strong>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    (function () {
        const container = document.getElementById('authContainer');
        if (!container) {
            return;
        }

        const views = Array.from(container.querySelectorAll('[data-auth-view]'));
        const toggles = Array.from(container.querySelectorAll('[data-auth-toggle]'));

        function setState(state) {
            const nextState = state === 'register' ? 'register' : 'login';
            container.classList.toggle('login-state', nextState === 'login');
            container.classList.toggle('register-state', nextState === 'register');
            container.dataset.authState = nextState;

            views.forEach((view) => {
                const active = view.dataset.authView === nextState;
                view.classList.toggle('is-active', active);
                view.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
        }

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', function () {
                setState(this.dataset.authToggle);
            });
        });
    }());
</script>
