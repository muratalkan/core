@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Widgetlar")}}</li>
        </ol>
    </nav>
    <button class="btn btn-success" onclick="window.location.href = '{{route('widget_add_page')}}'">{{__("Widget Ekle")}}</button>
    <br><br>

    @include('l.errors')
    @include('l.modal',[
        "id"=>"add_server",
        "title" => "Sunucu Ekle",
        "url" => route('widget_add'),
        "next" => "addToTable",
        "inputs" => [
            "Sunucu Seçin:server_id" => objectToArray(servers(),"name","id"),
            "Eklenti Seçin:extension_id" => objectToArray(extensions(),"name","id"),
            "Başlık" => "title:text",
            "type:-" => "type:hidden",
            "display_type:-" => "display_type:hidden"
        ],
        "submit_text" => "Ekle"
    ])
    <?php
        foreach($widgets as $widget){
            $server = \App\Server::find($widget->server_id);
            if($server){
                $widget->server_name = $server->name;
            }else{
                $widget->server_name = "Sunucu Silinmis";
            }
        }
    ?>
    @include('l.table',[
        "value" => $widgets,
        "title" => [
            "Sunucu" , "Başlık" , "Sunucu Adi", "*hidden*"
        ],
        "display" => [
            "server_name" , "title" ,"server_name", "id:widget_id"
        ],
        "menu" => [
            "Düzenle" => [
                "target" => "edit",
                "icon" => "fa-edit"
            ],
            "Sil" => [
                "target" => "delete",
                "icon" => "fa-trash"
            ]
        ]
    ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Widget Düzenle",
        "url" => route('widget_update'),
        "next" => "updateTable",
        "inputs" => [
            "Sunucu Seçin:server_id" => objectToArray(servers(),"name","id"),
            "Eklenti Seçin:extension_id" => objectToArray(extensions(),"name","id"),
            "Başlık" => "title:text",
            "type:-" => "type:hidden",
            "display_type:-" => "display_type:hidden",
            "widget_id:widget_id" => "widget_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Widget'ı Sil",
       "url" => route('widget_remove'),
       "text" => "Widget'ı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Widget Id:'null'" => "widget_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
    ])
@endsection