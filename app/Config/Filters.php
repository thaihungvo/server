<?php namespace Config;

use CodeIgniter\Config\BaseConfig;

class Filters extends BaseConfig
{
	// Makes reading things below nicer,
	// and simpler to change out script that"s used.
	public $aliases = [
		"csrf"     => \CodeIgniter\Filters\CSRF::class,
		"toolbar"  => \CodeIgniter\Filters\DebugToolbar::class,
		"honeypot" => \CodeIgniter\Filters\Honeypot::class,
		"auth" => \App\Filters\APIAuth::class,
        "board" => \App\Filters\Board::class,
        "boardstacks" => \App\Filters\BoardStacks::class,
        "boardtasks" => \App\Filters\BoardTasks::class,
        "task" => \App\Filters\Task::class,
	];

	// Always applied before every request
	public $globals = [
		"before" => [
			// "auth"
			// "csrf",
		],
		"after"  => [
			"toolbar",
			//"honeypot"
		],
	];

	// Works on all of a particular HTTP method
	// (GET, POST, etc) as BEFORE filters only
	//     like: "post" => ["CSRF", "throttle"],
	public $methods = [];

	// List filter aliases and any before/after uri patterns
	// that they should run on, like:
	//    "isLoggedIn" => ["before" => ["account/*", "profiles/*"]],
	public $filters = [
        "auth" => [
            "before" => ["api/*"]
        ],
        "task" => ["before" => [
            "/api/*/boards/*/tasks",
            "/api/*/boards/*/tasks/*"            
        ]],
        "board" => ["before" => [
                "/api/*/boards/*",
                "/api/*/boards/*/tags",
                "/api/*/boards/*/tags/*",
                "/api/*/boards/*/stacks"
            ]
        ],
        "boardstacks" => [
            "before" => [
                "/api/*/stacks/*",
                "/api/*/stacks/*/tasks",
                "/api/*/stacks/*/tasks/*"
            ]
        ],
        "boardtasks" => [
            "before" => [
                "/api/*/tasks/*",
                "/api/*/files/*"
            ]
        ]
    ];
}
