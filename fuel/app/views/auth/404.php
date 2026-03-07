<header class="login-header">
    <span class="login-header__title">ページが見つかりません</span>
</header>

<main class="login-main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="login-card">
                    <p class="login-error" role="alert">
                        お探しのページは存在しないか、移動した可能性があります。
                    </p>
                    <p>
                        <?php echo Html::anchor(Uri::create('auth/login'), 'ログイン画面へ戻る', array('class' => 'btn btn-primary')); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
