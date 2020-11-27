<?php

namespace App\System;

use GuzzleHttp\Client;
use App\Models\SystemSettings;

class Helper
{
    private $engine;
    public function __construct()
    {
        $this->client = new Client([
            "base_uri" => getProxyServer(),
            "verify" => false,
            "headers" => [
                "liman-system" => getGoEnginePassword()
            ]
        ]);
    }

    public function userAdd($extension_id)
    {
        try {
            $this->client->request(
                'POST',
                "/userAdd",
                [
                    "form_params" => [
                        "extension_id" => $extension_id
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function userRemove($extension_id)
    {
        try {
            $this->client->request(
                'POST',
                "/userRemove",
                [
                    "form_params" => [
                        "extension_id" => cleanDash($extension_id)
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function dnsUpdate($server1, $server2, $server3)
    {
        $arr = [
            "server1" => $server1,
            "server2" => $server2,
            "server3" => $server3,
        ];

        SystemSettings::updateOrCreate(
            ['key' => 'SYSTEM_DNS'],
            ['data' => json_encode($arr)]
        );

        return true;
    }

    public function addCertificate($tmpPath, $targetName)
    {
        $arr = [
            "certificate" => file_get_contents($tmpPath),
            "targetName" => $targetName
        ];
        
        $current = SystemSettings::where("key", "SYSTEM_CERTIFICATES")->first();

        if ($current) {
            $foo = json_decode($current->data, true);
            $flag = true;
            for ($i = 0; $i < count($foo); $i++) {
                if ($foo[$i]["targetName"] == $targetName) {
                    $foo[$i]["certificate"] = $arr["certificate"];
                    $flag = false;
                    break;
                }
            }
            
            if ($flag) {
                array_push($foo, $arr);
            }
            
            $current->update([
                "data" => json_encode($foo)
            ]);
        } else {
            SystemSettings::create([
                "key" => "SYSTEM_CERTIFICATES",
                "data" => json_encode([$arr])
            ]);
        }

        try {
            $this->client->request(
                'POST',
                "/certificateAdd",
                [
                    "form_params" => $arr,
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function removeCertificate($targetName)
    {
        $arr = [
            "targetName" => $targetName
        ];
        
        $current = SystemSettings::where("key", "SYSTEM_CERTIFICATES")->first();

        if ($current) {
            $foo = json_decode($current->data, true);
            for ($i = 0; $i < count($foo); $i++) {
                if ($foo[$i]["targetName"] == $targetName) {
                    unset($foo[$i]);
                    $foo = array_values($foo);
                    break;
                }
            }
            $current->update([
                "data" => $foo
            ]);
        }

        return true;
    }

    public function fixExtensionPermissions($extension_id, $extension_name)
    {
        return true;
    }

    public function runCommand($command)
    {
        try {
            $response = $this->client->request(
                'POST',
                "/runCommand",
                [
                    "form_params" => [
                        "command" => $command
                    ],
                ]
            );
        } catch (\Exception $e) {
            return __("Liman Sistem Servisine Erişilemiyor!");
        }
        return $response->getBody()->getContents();
    }
}
