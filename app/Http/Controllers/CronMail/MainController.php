<?php

namespace App\Http\Controllers\CronMail;

use App\Http\Controllers\Controller;
use App\Jobs\CronEmailJob;
use App\Models\CronMail;
use App\Models\Extension;
use App\Models\Server;
use App\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Carbon\Carbon;

class MainController extends Controller
{
    public function getMailTags()
    {
        $json = getExtensionJson(extension()->id);

        if (array_key_exists("mail_tags", $json)) {
            return respond($json["mail_tags"]);
        } else {
            return respond([]);
        }
    }

    public function addCronMail()
    {
        $obj = new CronMail(request()->all());
        $obj->last = Carbon::now()->subDecade();
        if ($obj->save()) {
            return respond("Mail ayarı başarıyla eklendi");
        } else {
            return respond("Mail ayarı eklenemedi!", 201);
        }
    }

    public function deleteCronMail()
    {
        $obj = CronMail::find(request("cron_id"));

        if ($obj == null) {
            return respond("Bu mail ayarı bulunamadı!");
        }

        if ($obj->delete()) {
            return respond("Mail ayarı başarıyla silindi");
        } else {
            return respond("Mail ayarı silinemedi!", 201);
        }
    }

    private $tagTexts = [];

    private function getTagText($key, $extension_id)
    {
        if (!array_key_exists($extension_id, $this->tagTexts)) {
            $json = getExtensionJson($extension_id);
            $this->tagTexts[$extension_id] = $json;
        }

        if (!array_key_exists("mail_tags", $this->tagTexts[$extension_id])) {
            return $key;
        }
        foreach ($this->tagTexts[$extension_id]["mail_tags"] as $obj) {
            if ($obj["tag"] == $key) {
                return $obj["description"];
            }
        }
        return $key;
    }

    public function getCronMail()
    {
        $mails = CronMail::all()->map(function ($obj) {
            $ext = Extension::find($obj->extension_id);
            if ($ext) {
                $obj->extension_name = $ext->display_name;
                $obj->tag_string = $this->getTagText($obj->target, $ext->id);
            } else {
                $obj->extension_name = "Bu eklenti silinmiş!";
                $obj->tag_string = "Bu eklenti silinmiş!";
            }

            $srv = Server::find($obj->server_id);
            if ($srv) {
                $obj->server_name = $srv->name;
            } else {
                $obj->server_name = "Bu sunucu silinmiş!";
            }

            $usr = User::find($obj->user_id);
            if ($usr) {
                $obj->username = $usr->name;
            } else {
                $obj->username = "Bu kullanıcı silinmiş!";
            }

            
            return $obj;
        });
        return view("settings.mail", [
            "cronMails" => $mails
        ]);
    }

    public function sendNow()
    {
        $obj = CronMail::find(request("cron_id"));

        if ($obj == null) {
            return respond("Bu mail ayarı bulunamadı!", 201);
        }

        $obj->update([
            "last" => Carbon::now()->subDecade()
        ]);

        $job = (new CronEmailJob(
            $obj
        ))->onQueue('cron_mail');
        app(Dispatcher::class)->dispatch($job);

        return respond("İşlem başlatıldı, tamamlandığında size mail ulaşacaktır.");
    }

    public function getView()
    {
        return view("settings.add_mail");
    }
}
