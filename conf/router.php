<?php
return [
    "rewrite_exp" => [
        "type" => "rewrite",
        "match" => "/rew/?$",
        "route" => [
            "controller" => "index",
            "action" => "rewrite",
        ],
    ],
    "regex_exp" => [
        "type" => "regex",
        "match" => '#^/reg/(\d+).html#',
        "route" => [
            "controlelr" => "index",
            "action" => "regex",
        ],
        "map" => [
            1 => "id",
        ],
    ],
]

?>
