<?php

// Hanya pesan yang dipakai aplikasi ini. Untuk lengkapnya: php artisan lang:publish lalu terjemahkan.
return [
    'required'         => ':attribute wajib diisi.',
    'required_without' => ':attribute wajib diisi.',
    'numeric'          => ':attribute harus berupa angka.',
    'integer'          => ':attribute harus berupa bilangan bulat.',
    'string'           => ':attribute harus berupa teks.',
    'date'             => ':attribute bukan tanggal yang valid.',
    'exists'           => ':attribute yang dipilih tidak valid.',
    'enum'             => ':attribute yang dipilih tidak valid.',
    'unique'           => ':attribute sudah digunakan.',
    'before_or_equal'  => ':attribute harus sebelum atau sama dengan :date.',
    'gt'               => ['numeric' => ':attribute harus lebih dari :value.'],
    'min'              => ['numeric' => ':attribute minimal :min.'],
    'max'              => [
        'numeric' => ':attribute maksimal :max.',
        'string'  => ':attribute maksimal :max karakter.',
    ],
    'custom'     => [],
    'attributes' => [],
];
