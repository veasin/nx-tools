```
        ___  ___
     __ \  \/  /
    /  \ \  \ /
   /  / \ \  \
  /  /\  / \  \   nx-tools
 /__/  \/__/\__\  vea
```

# nx-tools

NX Scaffolding

> composer require veasin/nx-tools --dev

# use

create app => src/app.php
> .\vendor\bin\nx app --ns=com

create setup => src/setup.php
> .\vendor\bin\nx setup

create web index => web/index.php
> .\vendor\bin\nx index
 
create model => src/models/user.php & users.php
> .\vendor\bin\nx model user

create model => src/models/corp/user.php & users.php
> .\vendor\bin\nx model corp/user

parse annotations: router & http => client.http & src/route.php
> .\vendor\bin\nx annotate

composer.json
```json
{
  "require-dev": {
    "veasin/nx-tools":">=0.0.9"
  },
  "config": {
    "process-timeout": 0
  },
  "scripts": {
    "dev": "@php -S localhost:8080 web/index.php",
    "annotate": "nx annotate"
  }
}
```

# annotation

```php
use nx\annotations\http\Client;
use nx\annotations\router\Get;
use nx\annotations\router\REST;
#[
    Client(Var:[
        'port'=>"8081",
    ]),
    Get("/user/d:uid", "get"),
    Client(Route:['put', '/user'],
        Throw: [404,401, 200],
        Return: ['id'=>'ID','nick-name'=>['name'=>'Nick Name', 'type'=>'string'],'email'=>'EMail'],
        Note: "Login",
        Response: [
            'body'=>['id'=>'user-id', 'corp-id'=>'corp-id'],
            'header'=>['token'=>'token'],
        ],
        Var: [
            'auth-token'=>"TOKEN id={{user-id}};token={{token}}"
        ],
    ),
    Client('get', '/user/{{user-id}}',
        Route:['get', '/user/d:uid', 'get'],
        Auth: "{{auth-token}}",
        Note: "Get User By ID",
    ),
    REST("/console/user", "/d:user_id", "list,add,get,update,delete"),
    Client(
        Route: ['rest', "/console/user", "/d:user_id", "list,add,get,update,delete"],
        Auth: "{{auth-token}}",
        Note: "Console Users"
    )
]
class user{
   	#[Get("/debug/ok")]
   	#[Client('get', '/debug/ok', Throw: [200], Note: "测试", Test: ['status'=>[200=>'OK']])]
	public function ok(): void{
	
	}
	#[Client(Route: ['get', '/debug/ok2'], Throw: [200], Note: "测试", Test: ['status'=>[200=>'OK']])]
	public function ok2(): void{
	    $this->out('ok');
	}
}


```
