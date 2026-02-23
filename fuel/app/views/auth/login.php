<!-- ヘッダー帯 -->
<header class="login-header">
    <span class="login-header__title">ログイン画面</span>
</header>

<!-- メインコンテンツ -->
<main class="login-main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="login-card">

                    <!-- エラーメッセージ -->
                    <?php if (isset($error) && $error): ?>
                    <div class="login-error" role="alert">
                        ID又はパスワードが間違っています
                    </div>
                    <?php endif; ?>

                    <!-- ログインフォーム -->
                    <?php echo Form::open(['action' => Uri::create('auth/login'), 'method' => 'post', 'class' => 'login-form']); ?>

                        <div class="login-form__group">
                            <label class="login-form__label" for="username">ID</label>
                            <input
                                class="login-form__input"
                                type="text"
                                id="username"
                                name="username"
                                value="<?php echo isset($username) ? e($username) : ''; ?>"
                                autocomplete="username"
                            >
                        </div>

                        <div class="login-form__group">
                            <label class="login-form__label" for="password">パスワード</label>
                            <input
                                class="login-form__input"
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                            >
                        </div>

                        <div class="login-form__submit">
                            <button type="submit" class="login-btn">ログイン</button>
                        </div>

                    <?php echo Form::close(); ?>

                </div><!-- /.login-card -->
            </div>
        </div>
    </div>
</main>
