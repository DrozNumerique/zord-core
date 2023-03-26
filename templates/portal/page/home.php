<?php if (isset($user) && $user->isKnown()) { ?>
<h1><?php echo $user->name; ?> (<?php echo $user->login; ?>)</h1>
<?php } ?>
<?php if ($user->hasRole('admin', $context)) { ?>
<a href="/admin">Admin</a><br>
<?php } ?>
<?php if ($user->isConnected()) { ?>
<a href="/disconnect">Disconnect</a><br>
<?php } else { ?>
<a href="/connect">Connection</a><br>
<?php } ?>
<?php echo Zord::getLocale('portal', $lang)->footer->enginedByZord; ?>
