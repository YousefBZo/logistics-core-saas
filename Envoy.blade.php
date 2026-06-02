@servers(['production' => '-i deploy_key user@your-server-ip'])

@setup
    $repository = 'git@github.com:YousefBZo/logistics-core-saas.git';
    $releases_dir = '/var/www/logistics/releases';
    $app_dir = '/var/www/logistics/current';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    pull_clone
    update_symlinks
    run_composer
    run_migrations
    restart_queues
@endstory

@task('pull_clone')
    echo 'Cloning repository...'
    [ -d {{ $releases_dir }} ] || mkdir -p {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
@endtask

@task('update_symlinks')
    echo 'Linking .env and storage structures...'
    ln -nfs /var/www/logistics/.env {{ $new_release_dir }}/.env
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs /var/www/logistics/storage {{ $new_release_dir }}/storage
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}
@endtask

@task('run_composer')
    echo 'Installing composer dependencies...'
    cd {{ $new_release_dir }}
    composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
@endtask

@task('run_migrations')
    echo 'Executing database migrations...'
    cd {{ $new_release_dir }}
    php artisan migrate --force
@endtask

@task('restart_queues')
    echo 'Restarting queue workers and caching config...'
    cd {{ $new_release_dir }}
    php artisan queue:restart
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
@endtask
