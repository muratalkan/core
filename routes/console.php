<?php

use App\User;
use App\Models\Module;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('administrator', function () {
    // Generate Password
    do {
        $pool = str_shuffle(
            'abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&'
        );
        $password = substr($pool, 0, 10);
    } while (
        !preg_match(
            "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/",
            $password
        )
    );
    $user = User::where([
        "name" => "Administrator",
        "email" => "administrator@liman.dev",
    ])->first();
    if ($user) {
        $user->update([
            "password" => Hash::make($password),
        ]);
    } else {
        $user = new User();
        $user->fill([
            "name" => "Administrator",
            "email" => "administrator@liman.dev",
            "password" => Hash::make($password),
            "status" => 1,
        ]);
    }
    $user->save();

    $this->comment("Liman MYS Administrator Kullanıcısı");
    $this->comment("Email  : administrator@liman.dev");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');

Artisan::command('scan:translations', function () {
    if (env('EXTENSION_DEVELOPER_MODE') != true) {
        return $this->error(
            "You need to open extension developer mode for use this function."
        );
    }
    $extension_path = "/liman/extensions/";
    $extensions = glob($extension_path . '/*', GLOB_ONLYDIR);
    $this->info("Started to scanning extension folders.");
    foreach ($extensions as $extension) {
        $this->comment("Scanning: " . $extension);
        $output = "$extension/lang/en.json";
        $translations = scanTranslations($extension);
        if (!is_dir(dirname($output))) {
            mkdir(dirname($output));
        }
        if (is_file($output)) {
            $translations = (object) array_merge(
                $translations,
                (array) json_decode(file_get_contents($output))
            );
        }
        file_put_contents(
            $output,
            json_encode(
                $translations,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );
        $this->info("Scanned and saved to " . $output);
    }
    $this->info("Finished scanning extension folders.");

    $this->info("Started to scanning server files.");
    $server_path = "/liman/server";
    $this->comment("Scanning: " . $server_path);
    $output = "$server_path/resources/lang/en.json";
    $translations = scanTranslations($server_path);
    if (is_file($output)) {
        $translations = array_merge(
            $translations,
            (array) json_decode(file_get_contents($output))
        );
    }
    file_put_contents(
        $output,
        json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    $this->comment("Scanned and saved to " . $output);
})->describe('Scan missing translation strings');

Artisan::command('module:add {module_name}', function ($module_name) {
    // Check if files are exists.
    $basePath = "/liman/modules/$module_name";

    if (!is_dir($basePath) || !is_file($basePath . "/db.json")) {
        return $this->error("Modül okunamadı!");
    }

    //Check if module supported or not.
    $json = json_decode(file_get_contents($basePath . "/db.json"), true);
    if (getVersionCode() < intval(trim($json["minLimanSupported"]))) {
        return $this->error(
            "Bu modülü yüklemek için önce liman'ı güncellemelisiniz!"
        );
    }

    $flag = Module::where(["name" => $module_name])->exists();

    if (!$flag) {
        $module = Module::create(["name" => $module_name, "enabled" => true]);

        $notification = new AdminNotification([
            "title" => "Yeni Modül Eklendi",
            "type" => "new_module",
            "message" => "$module_name isminde bir modül sisteme eklendi.",
            "level" => 3,
        ]);
    } else {
        $notification = new AdminNotification([
            "title" => $module_name . " modülü güncellendi.",
            "type" => "new_module",
            "message" => "$module_name isminde bir modül güncellendi.",
            "level" => 3,
        ]);
    }

    $notification->save();
    $this->info("Modül başarıyla yüklendi.");
})->describe("New module add");

Artisan::command('module:remove {module_name}', function ($module_name) {
    $module = Module::where('name', $module_name)->first();

    if (!$module) {
        return $this->error("Modul bulunamadi!");
    }

    $flag = $module->delete();

    if ($flag) {
        $this->info("Modul basariyla silindi.");
    } else {
        $this->error("Modul silinemedi.$flag");
    }
})->describe("Module remove");

Artisan::command('update_system_settings', function () {
    updateSystemSettings();
})->describe("Update system settings on database");

Artisan::command('open_postgresql', function () {
    if (trim(`id -u`) != "0") {
        $this->error("Bu komutu root olarak çalışmalısınız!");
        return;
    }
    if (!$this->confirm('Bunu yaparak postgresql bağlantınızı her yerden erişilebilir yapacaksınız, emin misiniz?')) {
        return;
    }
    $output = `
    sed -i '/#liman/d' /etc/postgresql/13/main/postgresql.conf 2>&1;
    sed -i '/#liman/d' /etc/postgresql/13/main/pg_hba.conf 2>&1;
    printf '\nlisten_addresses=:0.0.0.0 #liman\n' | sudo tee -a /etc/postgresql/13/main/postgresql.conf 2>&1;
    printf '\nhost    all     all     0.0.0.0/24  md5 #liman\n' | sudo tee -a /etc/postgresql/13/main/pg_hba.conf 2>&1;
    `;
    $this->info($output);
    if (!$this->confirm('İşlem tamamlandı, ayrıca postgresql servisini yeniden başlatmak ister misiniz?')) {
        return;
    }
    `systemctl restart postgresql`;
})->describe("Open postgresql connection to outside.");

Artisan::command('close_postgresql', function () {
    if (trim(`id -u`) != "0") {
        $this->error("Bu komutu root olarak çalışmalısınız!");
        return;
    }
    `
    sed -i '/#liman/d' /etc/postgresql/13/main/postgresql.conf;
    sed -i '/#liman/d' /etc/postgresql/13/main/pg_hba.conf;
    `;

    if (!$this->confirm('İşlem tamamlandı, ayrıca postgresql servisini yeniden başlatmak ister misiniz?')) {
        return;
    }
    `systemctl restart postgresql`;
})->describe("Close postgresql connection from outside.");

Artisan::command('show_db_password', function () {
    if (trim(`id -u`) != "0") {
        $this->error("Bu komutu root olarak çalışmalısınız!");
        return;
    }
    
    $this->info("Veritabanı parolası : " . env("DB_PASSWORD"));
})->describe("Show database password");
