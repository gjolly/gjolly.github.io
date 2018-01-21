<?php

	// Mail settings
	$to      = "contact@gauthierjolly.com";
	$subject = "Contact Form";

	// You can put here your email
	$header = "From: contactform@gauthierjolly.com\r\n";
	$header.= "MIME-Version: 1.0\r\n";
	$header.= "Content-Type: text/plain; charset=utf-8\r\n";
	$header.= "X-Priority: 1\r\n";

	if (isset($_POST["name"]) && isset($_POST["email"]) && isset($_POST["message"])) {

		$content  = "Name: "      . $_POST["name"]    . "\r\n";
		$content .= "Email: "     . $_POST["email"]   . "\r\n";
		$content .= "Message: "   . "\r\n" . $_POST["message"];

		if (mail($to, $subject, $content, $header)) {
			$result = array(
				"message"    => "Thanks for contacting me.",
				"sendstatus" => 1
			);
			echo json_encode($result);
		} else {
			$result = array(
				"message"    => "Sorry, something is wrong. However, you can still send an email to <a href='mailto:contact@gauthierjolly.com'>contact@gauthierjolly.com</a>.",
				"sendstatus" => 0
			);
			echo json_encode($result);
		}

	}

?>
