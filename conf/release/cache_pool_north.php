<?php

return [
    'test' => [
        "backend" => "Redis",
        "config" => [
            'master' => [
                "127.0.0.1:6379",
            ],
            'slave' => [
                "127.0.0.1:6379",
            ]
        ]
    ],
];