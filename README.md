# k-repository
generator repository laravel 5.*

install
``` bash
$ composer require kingdarkness/k-repository
``
Edit your `AppServiceProvider` to add the following to the `register` method:

``` php
$this->app->register(\KRepository\KRepositoryServiceProvider::class);
if (!empty(config('kproviders'))) {
    foreach (config('kproviders') as $provider )
    {
        $this->app->register( $provider );
    }
}
```

Then execute the command:

``` bash
$ php artisan vendor:publish  --provider="KRepository\KRepositoryServiceProvider"
```

Edit `config/krepository.php` to your needs.

Example config

``` php
return [
    'path' => 'King',
    'files' => [
        'model' => '{name}',
        'interface' => '{name}Repository',
        'data_mapper' => 'Db{name}Repository'
    ],
    'parent' => [
        // data mapper parent class configs
        'data_mapper' => [
            'config' => true,
            'class_name' => 'BaseRepository',
            'namespace' => 'Darkness\King'
        ],
        'model' => [
            'config' => true,
            'class_name' => 'Entity',
            'namespace' => 'Darkness\King'
        ]
    ]
];
```

To create a simple repository:
``` bash
$ php artisan make:repository User
```
To create a repository with migration:
``` bash
$ php artisan make:repository User --migration=true
```
The repository will generator in `app/King/Users`

The migration in `database/migrations`

The ServiceProvider in `app/Providers`

The repository will automatic register ServiceProvider in `configs/kproviders.php`
