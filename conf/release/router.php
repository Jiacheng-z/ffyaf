<?php
return [

    "example_rewrite" => [
        "type" => "rewrite",
        "match" => "/rewrite.html$",
        "route" => [
            "controller" => "index",
            "action" => "rewrite",
        ],
    ],

    "example_regex" => [
        "type" => "regex",
        "match" => "#/regex/(\d+).html#",
        "route" => [
            "controller" => "index",
            "action" => "regex",
        ],
        "map" => [
            1 => "id",
        ],
    ],


];

?>
