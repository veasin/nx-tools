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

parse annotations: router & http => route.http & src/route.php
> .\vendor\bin\nx annotate

composer.json
```json
{
  "require-dev": {
    "veasin/nx-tools":"^0.0.4"
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
