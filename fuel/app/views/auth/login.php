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
                    <!-- isset() はPHPの組み込み関数で、「その変数が定義されていて、かつ null ではないか」を調べる -->
                    <!-- （上記）かつ、$error が 真（true） かどうか。 -->
                    <?php if (isset($error) && $error): ?>
                    <div class="login-error" role="alert">
                        ID又はパスワードが間違っています
                    </div>
                    <?php endif; ?>

                    <!-- ログインフォーム -->
                    <?php echo Form::open(['action' => Uri::create('auth/login'), 'method' => 'post', 'class' => 'login-form']); ?>
                    <?php echo Form::csrf(); ?>
                        <div class="login-form__group">
                        <!-- label の for="username" と input の id="username" を揃えると、ラベルをクリックしたときに入力欄にフォーカスが移る。 -->
                        <!-- name="username" … サーバー側で Input::post('username') として受け取る名前。コントローラと一致させる必要があります。 -->
                        <!-- e() は FuelPHP でよくある HTML 用のエスケープ（特殊文字を安全な形に変換） の関数。< や " を そのまま HTML に書かない ようにして、表示の崩れや XSS（悪意のあるスクリプト混入） を防ぎます。 -->
                        <!-- autocomplete="username" … ブラウザのパスワードマネージャーなどが「これはユーザー名欄」と認識しやすくなります。 -->
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

                </div>
            </div>
        </div>
    </div>
</main>
