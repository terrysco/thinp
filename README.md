#What's thinp
thinp is a light-weight php framework focusing on creating simple back-end services for mobile apps.

##Features
- Data Format: JSON
- Cache Component: Redis
- Database: MySQL

#Requirements
- PHP Version >= 5.3
- MySQLi
- Redis

#Installation
retrieve the whole library and find out modules directory, now you can write your own modules there.

#Configuration
please checkout the config.inc.php file, you could set up your specific database connection credentials here.
- default module and handler

- url suffix

if you specify a url suffix, say ".html", then '/api/default.html' will map to function 'api_default'

- niginx rewrites

```
location /thinp {
    if (!-e $request_filename) {
        rewrite ^/(.*) /index.php?act=$1 last;
    }
}
```

#Clarification
since I've been trying the best to keep thinp simple and easy to use, I didn't implement quite "heavy" components, so if you need the following features, I'm sorry that thinp is not for you. Anyway, you can check out yii (also my favorite php framework). git it a shot, why not? :-)
- Active Record
- Full Featured of MVC
- Come up with HTML & PHP Template
- etc.

#Documentation
- Mysql Database Manipulation

```
l('db')->get('users');
l('db')->where('id=?', 1)->get('users');
```
- HTTP GET and POST

```
get_query('username', 'default');
get_post('username', 'default');
get_input();
```
- Retrieve External Resource

```
do_post('http://www.xxx.com/api/service.json', array('name'=>'user', 'passwd'=>'pass));
do_get('http://www.yyy.com/api/list.xml', array('id'=>123));
```
- Redis Cache API

```
cache_set('username', 'terrysco', 30);
cache_get('username', 'default');
```

note that this API is using a simple redis string structure, if you want to design a complicated structure,
you should using the following redis library.

- Redis Library

documentation: https://github.com/nicolasff/phpredis

```
l('redis')->incr($name);
l('redis')->mGet(array('key1', 'key2', 'key3'));
...
```
