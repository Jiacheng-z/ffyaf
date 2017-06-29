<?php

return [
    "example" => [
        "backend" => "Redis",
        "config" => [
            'master' => [
                "localhost:6379",
            ],
            'slave' => [
                "localhost:6379",
            ]
        ]
    ],
];
