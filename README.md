#What's thinp
thinp is a light-weight php framework focusing on creating simple back-end services for mobile apps.

##Features
- Data Format: JSON
- Cache Component: Redis
- Database: MySQL

#Requirements
- PHP Version >= 5.3
- MySQLi

#Installation
retrieve the whole library and find out modules directory, now you can write your own modules there.

#Configuration
please checkout the config.inc.php file, you could set up your specific database connection credentials here.

- niginx rewrites

```
location /thinp {
    if (!-e $request_filename) {
        rewrite ^/(.*) /index.php?act=$1 last;
    }
}
```

#Clarification
since I've been tring the best to keep thinp simple and easy to use, I didn't implement quite "heavy" components, so if you need the following features, I'm sorry that thinp is not for you. Anyway, you can check out yii (also my favorite php framework). git it a shot, why not? :-)
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
```
- Retrieve External Resource

```
do_post('http://www.xxx.com/api/service.json', array('name'=>'user', 'passwd'=>'pass));
do_get('http://www.yyy.com/api/list.xml', array('id'=>123));
```
