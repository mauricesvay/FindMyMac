#!/usr/bin/php
<?php
/*
Copyright (c) 2010, Maurice Svay
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

* Redistributions of source code must retain the above copyright notice,
this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
* Neither the name of Maurice Svay nor the names of its
contributors may be used to endorse or promote products derived from
this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER
OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

error_reporting(0);
include dirname(__FILE__).'/lib/geoloc.php';

if (empty($argv[1])) {
    echo "Usage: " . $argv[0] . " email@example.com\n";
    die;
}

define("EMAIL_ADDRESS", $argv[1]);
define("WHATISMYIP_URL", "http://www.whatismyip.org/");

//Gather data
$location = print_r(geolocate(), 1);
$ip = file_get_contents(WHATISMYIP_URL);
$date = date("Y-m-d H:i:s");
exec("./bin/imagesnap snapshot.jpg");
exec("/usr/sbin/screencapture screenshot.png");

//Build e-mail
$subject = "FindMyMac " . $date;

$body = "DATE : " . $date . "\r\n";
$body.= "IP : " . $ip . "\r\n";
$body.= "LOCATION : " . $location . "\r\n";

$snapshot_attachment = chunk_split(base64_encode(file_get_contents('snapshot.jpg')));
$screenshot_attachment = chunk_split(base64_encode(file_get_contents('screenshot.png')));
$mime_boundary = "==Multipart_Boundary_x{" . md5(uniqid()) . "}x";

$headers = "From: " . EMAIL_ADDRESS ."\r\n";
$headers.= "MIME-Version: 1.0\r\n";
$headers.= "Content-Type: multipart/mixed;\r\n";
$headers.= "boundary=\"" . $mime_boundary . "\"\r\n\r\n";
$headers.= "This is a multi-part message in MIME format.\r\n";

$headers.= "--" . $mime_boundary . "\r\n";

$headers.= "Content-type:text/plain; charset=iso-8859-1\r\n";
$headers.= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$headers.= $body."\r\n\r\n";

$headers.= "--" . $mime_boundary . "\r\n";

$headers.= "Content-Type: application/octet-stream; name=\"snapshot.jpg\"\r\n"; // use diff. tyoes here
$headers.= "Content-Transfer-Encoding: base64\r\n";
$headers.= "Content-Disposition: attachment; filename=\"snapshot.jpg\"\r\n\r\n";
$headers.= $snapshot_attachment."\r\n\r\n";

$headers.= "--" . $mime_boundary . "\r\n";

$headers.= "Content-Type: application/octet-stream; name=\"screenshot.png\"\r\n"; // use diff. tyoes here
$headers.= "Content-Transfer-Encoding: base64\r\n";
$headers.= "Content-Disposition: attachment; filename=\"screenshot.png\"\r\n\r\n";
$headers.= $screenshot_attachment."\r\n\r\n";

//Send e-mail
mail(EMAIL_ADDRESS, $subject, "", $headers);
//or you could post everything to a an URL in case emails are blocked