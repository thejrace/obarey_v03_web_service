<?php

	interface Request {
		public function send();
		public function cancel();
		public function action( $accept );
	}
