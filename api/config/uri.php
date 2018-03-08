<?php
return [
	"/" => "index",
	'<m:(xqg)>/<controller:(user|order|work-order)>/<action:\w+>' => 'third/<controller>/<action>',
	'<m:(zhima)>/<controller:(authorize|index)>/<action:\w+>' => 'zhima/<controller>/<action>',
	'<m:(duiba)>/<controller:(index)>/<action:\w+>' => 'duiba/<controller>/<action>',
	"<controller:\w+>/<action:\w+>/<id:\d+>" => "<controller>/<action>",
	"<controller:\w+>/<action:\w+>" => "<controller>/<action>",

];

?>