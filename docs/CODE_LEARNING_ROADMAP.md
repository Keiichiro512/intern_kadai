# コード理解ロードマップ（初心者エンジニア向け）

このドキュメントは、本アプリのコードを**ゼロから順に理解していくための学習順序**をまとめたものです。  
「どのファイルから読めばよいか」「全体を理解するにはどう進めればよいか」の指針として使ってください。

---

## 最初に読むべきファイル（スタート地点）

**おすすめのスタートは次の 3 つです。**

1. **`public/index.php`**  
   「リクエストがどこから入るか」を理解する。約 130 行で、最後に `bootstrap.php` を読んでいる流れを追う。

2. **`fuel/app/config/routes.php`**  
   「URL と処理の対応表」として読む。どのURLがどのコントローラに繋がるかが一覧で分かる。

3. **`fuel/app/classes/controller/auth.php`**（特に `action_login`）  
   一番シンプルな「1画面の流れ」が一つのファイルにまとまっている。MVC の C（コントローラ）の役割が分かる。

この 3 つを**この順番**で読むと、「リクエスト → ルート → コントローラ」という骨格が頭に入ります。

---

## 全体ロードマップ（フェーズ別）

以下のフェーズを**上から順に**進めると、全体を無理なく理解できます。

---

### フェーズ 1：リクエストの入口とルーティング（1〜2時間）

**目標**  
「ブラウザで URL を叩いたとき、どのファイルがどの順で実行されるか」を説明できるようになる。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `public/index.php` | 定数（DOCROOT, APPPATH など）の意味、最後の `require APPPATH.'bootstrap.php'` でアプリが起動することを確認する。 |
| 2 | `fuel/app/bootstrap.php` | Core の読み込み、`Model` の登録、`Fuel::init('config.php')` で設定が読み込まれる流れを追う。 |
| 3 | `fuel/app/config/routes.php` | `_root_` がログイン画面になっていること、`auth/login` が `auth/login`（Controller_Auth::action_login）に繋がることを確認する。 |

**理解のチェック**  
「`/` にアクセスしたら、なぜログイン画面が表示されるか」を、index.php → bootstrap → routes の流れで説明できる。

---

### フェーズ 2：ログインの 1 本の流れで MVC を理解する（2〜3時間）

**目標**  
「1 つの画面（ログイン）が、Controller → Model → View でどう組み立てられているか」を追えるようになる。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `fuel/app/classes/controller/auth.php` | `action_login()` だけに絞って読む。GET でフォーム表示、POST でユーザー検索・パスワード照合・Session 保存・リダイレクト、という流れを掴む。 |
| 2 | `fuel/app/classes/model/user.php` | `users` テーブルと対応していること、`Model_User::find()` でユーザーが取れることを確認する。 |
| 3 | `fuel/app/views/auth/login.php` | コントローラから渡される `$error` と `$username` の使われ方、`Form::open(['action' => Uri::create('auth/login')])` で送信先が `auth/login` になっていることを確認する。 |

**理解のチェック**  
「ログインボタンを押したあと、どのメソッドが実行され、どこで DB を参照し、どこで HTML が作られているか」を説明できる。

---

### フェーズ 3：共通レイアウトと認可の仕組み（1〜2時間）

**目標**  
「どの画面も同じヘッダーになる理由」と「未ログイン・権限違いでリダイレクトされる理由」を理解する。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `fuel/app/views/template.php` | 全画面の土台。`$content` に各画面のビューが入ること、ログイン中はヘッダー（挨拶・ホーム・ログアウト）が出ることを確認する。 |
| 2 | `fuel/app/classes/controller/base.php` | `check_access()` で Session の有無と `role_id` をチェックし、`auth/login` や `auth/access_denied` に飛ばしている流れを追う。 |
| 3 | `fuel/app/classes/controller/auth.php`（再読） | 認証用なので `Controller_Base` ではなく `Controller_Template` を継承している理由を考える（ログイン前でもアクセスできるため）。 |

**理解のチェック**  
「塾長用の URL に生徒でログインしたままアクセスすると、なぜアクセス拒否画面になるか」を Base の `check_access()` で説明できる。

---

### フェーズ 4：塾長（admin）機能で「複数画面・複数モデル」を追う（3〜5時間）

**目標**  
「一つのロール（塾長）の複数画面が、どのコントローラ・ビュー・モデルで実装されているか」を把握する。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `fuel/app/config/routes.php`（admin 部分） | `admin/home`、`admin/schedule`、`admin/user_create`、`admin/users/edit` などがどのアクションに繋がるか確認する。 |
| 2 | `fuel/app/classes/controller/admin.php` | まず `action_home()`、次に `action_schedule_home()` や `action_schedule_save()`、その後に `action_create()` / `action_user_list()` / `action_edit_user()` の順で読む。どのアクションがどのビュー・モデルを使っているかメモする。 |
| 3 | `fuel/app/views/admin/home.php` | 塾長ホームの表示内容を確認。 |
| 4 | `fuel/app/views/admin/schedule/home.php` | スケジュール画面の構成（フォーム・一覧など）を確認。 |
| 5 | `fuel/app/views/admin/user_create.php`、`user_list.php`、`user_edit.php` | ユーザー CRUD の画面がコントローラのどのアクションと対応しているか対応付ける。 |
| 6 | `fuel/app/classes/model/lesson/schedule.php`、`lesson/schedule/student.php` | スケジュールと生徒の関連がどうモデルで表現されているかを見る。 |

**理解のチェック**  
「塾長がスケジュールを保存するとき、どのルート → どのアクション → どのモデルが呼ばれ、どのビューが表示されるか」を一連の流れで説明できる。

---

### フェーズ 5：講師・生徒・保護者（シンプルなホームのみ）（1時間）

**目標**  
「ロールごとに別コントローラがあること」と「それぞれホーム画面があること」を押さえる。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `fuel/app/classes/controller/teacher.php` | `action_home()` でビューを出しているだけのシンプルな流れを確認。 |
| 2 | `fuel/app/classes/controller/student.php` | 同様に `action_home()` の役割を確認。 |
| 3 | `fuel/app/classes/controller/parent.php` | 同様。 |
| 4 | `fuel/app/views/teacher/home.php`、`student/home.php`、`parent/home.php` | 表示内容の違い（あれば）を確認。 |

**理解のチェック**  
ログイン後のリダイレクト（auth の `role_id` による分岐）と、各ロールの `xxx/home` がそれぞれのコントローラの `action_home` に対応していることを説明できる。

---

### フェーズ 6：モデルと DB の関係（2〜3時間）

**目標**  
「テーブルとモデルクラスの対応」「リレーションの定義」を読めるようになる。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `fuel/app/classes/model/user.php` | `protected static $_table_name = 'users'`、`role_id` など主要なカラムとの対応を確認。 |
| 2 | `fuel/app/classes/model/role.php` | ロールマスタ。ユーザーと 1 対多の関係になっているか確認。 |
| 3 | `fuel/app/classes/model/student.php` | 生徒テーブル。他モデルとのリレーション（belongs_to, has_many など）があれば読む。 |
| 4 | `fuel/app/classes/model/lesson/schedule.php`、`lesson/schedule/student.php` | スケジュールと生徒の多対多（中間テーブル）のイメージを掴む。 |
| 5 | `fuel/app/migrations/001_*.php`、`002_*.php` | 実際のテーブル作成・変更の SQL を眺め、モデルとテーブルがどう対応しているか確認。 |

**理解のチェック**  
「ユーザーがログインするとき、どのテーブルを参照しているか」「スケジュールに生徒を紐付けるとき、どのテーブル（とモデル）が関わるか」を説明できる。

---

### フェーズ 7：設定・環境・静的ファイル（1時間）

**目標**  
「DB 接続や環境の違いがどこで決まるか」「CSS がどこにあるか」を把握する。

| 順番 | ファイル | 役割・読むときのポイント |
|------|----------|---------------------------|
| 1 | `fuel/app/config/config.php` | `base_url`、`always_load`（orm パッケージ）、`log_threshold` など、アプリ全体に効く設定を確認。 |
| 2 | `fuel/app/config/development/db.php` | 開発環境の DB（host: db、DB 名など）。Docker 利用時はここを変更することが多い。 |
| 3 | `fuel/app/config/crypt.php` | パスワードハッシュなどで使う暗号化設定があるか確認。 |
| 4 | `public/assets/css/auth.css`、`admin.css` | ログイン画面と管理画面のスタイルがどこで読み込まれるか（template の `$style_sheet`）と対応付ける。 |

**理解のチェック**  
「DB を別環境に変えたいとき、どのファイルをいじるか」「ログイン画面の見た目を変えたいとき、どの CSS を編集するか」を答えられる。

---

## ロードマップの全体像（図）

```
フェーズ1: 入口とルート
  index.php → bootstrap.php → routes.php
       ↓
フェーズ2: ログインの1本の流れ（MVC）
  Controller_Auth::action_login → Model_User → views/auth/login.php
       ↓
フェーズ3: 共通レイアウトと認可
  template.php / Controller_Base (check_access)
       ↓
フェーズ4: 塾長機能（複数画面・複数モデル）
  Controller_Admin + admin/*.php ビュー + Schedule/User 等のモデル
       ↓
フェーズ5: 講師・生徒・保護者
  Controller_Teacher / Student / Parent + 各 home ビュー
       ↓
フェーズ6: モデルとDB
  Model_* クラス + migrations
       ↓
フェーズ7: 設定・静的ファイル
  config/*.php, public/assets
```

---

## 学習のコツ

- **一度に全部読まない**  
  フェーズごとに「このフェーズの目標」だけを意識して、該当ファイルに絞って読む。

- **手を動かす**  
  ログイン処理の流れを追いながら、`action_login` に一時的に `Log::info(...)` などを仕込んで、実行順を確認する。

- **ルートとコントローラをセットで見る**  
  `routes.php` で「この URL → このアクション」を確認してから、そのアクションのコードを開く癖をつける。

- **分からないところはメモして後回しでよい**  
  FuelPHP の細かい仕様（`Input::method()`、`Session::set` など）は、使っている箇所を追いながら「こういう書き方でこう動く」と理解していけば十分です。

---

## 次のステップ（ロードマップ修了後）

- 既存の仕様を変える（例：ログイン後のリダイレクト先を変える、塾長画面に項目を足す）。
- 簡単な新機能を 1 つ追加する（例：講師用に「自分の担当一覧」画面を 1 つ追加）。
- テストやデプロイ用の設定（環境変数、本番用 config）を読む。

このロードマップに沿って進めれば、アプリ全体のコードを段階的に理解していけます。不明なファイルや処理が出てきたら、このドキュメントの「どのフェーズのどのファイルに近いか」を考えて、そのあたりからまた読み直すと整理しやすいです。
