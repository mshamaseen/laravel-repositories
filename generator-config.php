<?php
return
    [
        [
            'stub' => 'first.stub',
            'output' => 'first.generated',
            'replace' => [
                '{% to be replaced 1 %}' => 'value',
                '{% to be replaced 2 %}' => 'value'
            ]
        ],
        [
            'stub' => 'second.stub',
            'output' => 'second.generated',
            'replace' => [
                '{% to be replaced 1 %}' => 'value',
            ]
        ]
    ];
