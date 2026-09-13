<?php
declare(strict_types=1);
namespace Amanah\Services {
    function mail($to, $subject, $body, $headers): bool {
        $GLOBALS['audit_mail_calls'][] = ['kind'=>$subject, 'confirmation_link'=>str_contains($body, '/newsletter/confirmer/'), 'unsubscribe_link'=>str_contains($body, '/newsletter/desinscription/')];
        return $GLOBALS['audit_mail_success'];
    }
}
namespace {
    $root='C:/Users/diplk/Documents/amanah/backend';
    spl_autoload_register(function($class)use($root){$f=$root.'/src/'.str_replace('\\','/',substr($class,7)).'.php';if(is_file($f))require_once $f;});
    function db(){global $root;$d=new \Amanah\Infrastructure\Database(new \PDO('sqlite::memory:'));$d->pdo()->exec(file_get_contents($root.'/database/schema.sql'));return $d;}
    $result=[];
    foreach(['UTC','Europe/Zurich'] as $zone){date_default_timezone_set($zone);$d=db();$l=new \Amanah\Security\RateLimiter($d);$hits=[];for($i=0;$i<7;$i++)$hits[]=$l->allow('audit',5,3600);$result['rate_'.$zone]=$hits;}
    date_default_timezone_set('UTC');
    $d=db();$c=new \Amanah\Services\CommunicationService($d,new \Amanah\Security\RateLimiter($d));$c->subscribe('audit@example.invalid','127.0.0.1');
    $GLOBALS['audit_mail_success']=true;$GLOBALS['audit_mail_calls']=[];
    $j=new \Amanah\Services\JobRunner($d,['mail_to'=>'audit@example.invalid','mail_from'=>'audit@example.invalid','url'=>'http://localhost']);
    $result['jobs_success']=$j->run();$result['jobs_second_pass']=$j->run();$result['intercepted_messages']=$GLOBALS['audit_mail_calls'];
    $d=db();$c=new \Amanah\Services\CommunicationService($d,new \Amanah\Security\RateLimiter($d));$c->contact(['email'=>'audit@example.invalid','message'=>'Audit local'],'127.0.0.1');$GLOBALS['audit_mail_success']=false;
    $j=new \Amanah\Services\JobRunner($d,['mail_to'=>'audit@example.invalid','mail_from'=>'audit@example.invalid']);
    $j->run();$result['failed_first']=$d->fetchOne('SELECT status,attempts FROM outbox_messages');
    for($i=0;$i<4;$i++){$d->execute("UPDATE outbox_messages SET available_at='2000-01-01 00:00:00'");$j->run();}
    $result['failed_fifth']=$d->fetchOne('SELECT status,attempts FROM outbox_messages');$result['failed_jobs']=$d->fetchOne('SELECT COUNT(*) n FROM failed_jobs')['n'];
    $d=db();$now=\Amanah\Support\Clock::now();$hash=password_hash('Audit-only-password-fixture',PASSWORD_DEFAULT);
    $d->execute("INSERT INTO users(id,email,password_hash,status,created_at,updated_at) VALUES('audit-user','audit@example.invalid',:hash,'active',:now,:now)",['hash'=>$hash,'now'=>$now]);
    $d->execute("INSERT INTO roles(id,name) VALUES('audit-role','administrator')");$d->execute("INSERT INTO role_user(user_id,role_id) VALUES('audit-user','audit-role')");
    $a=new \Amanah\Services\AdminAuthService($d,'audit-only-test-key',new \Amanah\Security\RateLimiter($d));$a->login('audit@example.invalid','Audit-only-password-fixture',null,'127.0.0.1');$result['login_without_mfa']=$a->requireRole('administrator')==='audit-user';
    $d->execute("UPDATE users SET status='revoked'");try{$a->requireRole('administrator');$result['revoked_denied']=false;}catch(\RuntimeException){$result['revoked_denied']=true;}
    $a->logout();$result['real_emails_sent']=0;
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
