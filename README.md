# px2-kflow

[Pickles 2](https://pickles2.com/) に、kflowプロセッサー機能を追加します。


## Usage - 使い方

### 1. Pickles 2 プロジェクト をセットアップ

Pickles 2 の [クイックスタート](https://pickles2.com/getting_started/) ページを参照してください。

### 2. composer.json に追記

```
$ composer require pickles2/px2-kflow
```

### 3. config.php を更新

```php
$conf->funcs->processor->kflow = array(
    // kflow文法を処理する
    'pickles2\px2kflow\kflow::processor',

    // html のデフォルトの処理を追加
    $conf->funcs->processor->html,
);
```


## 更新履歴 - Change log

### pickles2/px2-kflow v0.1.1 (リリース日未定)

- アセットの保存先を公開キャッシュディレクトリに変更した。

### pickles2/px2-kflow v0.1.0 (2025年3月15日)

- Initial Release


## for Developer

### Test

```bash
$ cd {$documentRoot}
$ ./vendor/phpunit/phpunit/phpunit
```
