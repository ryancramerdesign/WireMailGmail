# WireMail: Gmail

Enables using the Google OAuth2 API for sending email in ProcessWire. Once
installed and configured, any email sent by ProcessWire is sent by Gmail. 

## Overview

### Benefits

The benefits of using Gmail are likely not a mystery—it’s about as reliable
as it gets when it comes to sending email, among numerous benefits. So we’ll 
instead focus on the benefits of using this particular module relative to using SMTP. 

The OAuth2 API is the way Google would prefer that you send email through Gmail. 
Gmail also supports sending via SMTP, but only if you turn on the setting in your 
Google account for “enable access for insecure apps”. Since doing that isn’t ideal, 
this module provides a way to send email the way Google prefers that you do. 
Presumably this is more safe, secure and reliable as a long term solution. 

### Drawbacks

Most of the Google services use the OAuth2 API, and the biggest drawback is just
that it requires more setup on your part. It’s not as simple as copying/pasting
a few email server settings into the field. Though it can still all be done from
the browser, but just takes a few extra steps. In particular, you’ll need the 
ProcessWire GoogleClientAPI module installed and configured before you can use
this WireMailGmail module. This is Step 1 in our installation instructions below.

### Other considerations

When using Gmail for sending your website’s email, consider that it 
requires the Gmail account to be the “from” email address for any messages sent.
You cannot use it to send emails with a “from” address that is something you are
not authenticated for. Meaning, you can’t spoof addresses. Though that’s both a
benefit and a drawback, depending on what your expectations are. However, you
do have full control over the from “name” and “reply-to”. So the actual “from” 
email address may never actually be seen by the email recipients. 

You probably don’t want to use a Gmail account for sending thousands of bulk 
messages. I don't know what Gmail's policies are regarding this, but I'm guessing 
they don't want you doing this. I personally think of Gmail as an excellent and 
reliable solution primarily for for transactional email, but maybe also for small 
scale newsletters (though I've not personally used it for that purpose). 

## Installation

### Step 1: 

Please read all of step 1 before proceeding. You must first install and configure the 
[GoogleClientAPI](https://github.com/ryancramerdesign/GoogleClientAPI) 
module (by the same author as this one). It has its own installation instructions. 
In the section where it asks for “scopes”, one of them should be this:
~~~~~
https://www.googleapis.com/auth/gmail.send
~~~~~
Note that when sending email with this module, it will send from whatever
Gmail account you authenticated it with. Gmail requires that that account be in 
the “from” header of any message sent with it. As a result, if you are a web
developer, you probably want this to be setup with your client’s account, rather
than your own. Or you may want to setup a new Google/Gmail account specifically 
for this purpose. 

### Step 2:

- Copy this module’s files into /site/modules/WireMailGmail/.
- In your admin, click to Modules > Refresh.
- On the “Site” tab of your Modules screen, click “Install” for WireMailGmail
- Review and optionally populate the “from” email and name fields. Save.

### Step 3: 

Test that everything is working by entering an email address in the last field
on the configuration screen. It will send a test message to whatever email you
enter there. If it encounters errors, they will appear as error notifications
in the admin and they will be recorded in Setup > Logs > wire-mail-gmail. 
If you receieve the test email, then that confirms that everything is working.
I also recommend testing in live scenarios on your website if/when possible.

### Step 4: (optional)

If WireMailGmail is the only WireMail module you have installed, then you 
can skip this step. However, if you have multiple WireMail modules installed,
and you want WireMailGmail to be the default one used by ProcessWire, then you
should add the following to your /site/config.php file:

~~~~~
$config->wireMail('module', 'WireMailGmail');
~~~~~

Have a great day!

--- 
Copyright 2019 by Ryan Cramer


