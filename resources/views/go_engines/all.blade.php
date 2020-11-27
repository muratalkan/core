@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Go Servisleri")}}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <h3 class="profile-username text-center">{{__("Go Servisleri")}}</h3>
                    <p class="text-muted text-center">{{__("Liman'ın bağlandığı go servislerini buradan görüntüleyebilirsiniz, yeni bir servis eklendiğinde otomatik olarak bu listeye eklenecektir.")}}</p>
                </div>
            </div>
        </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <div class="tab-pane active" id="settings" style="margin-top: 15px;">
                    @include('table',[
                    "value" => $engines,
                        "title" => [
                            "Makine ID" , "İp Adresi" , "Port", "Durum", "*hidden*"
                        ],
                        "display" => [
                            "machine_id" , "ip_address", "port", "enabled", "id:engine_id"
                        ]
                    ])
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection