<?php declare(strict_types = 1);

namespace ZxcvbnPhp\Feedback;

interface FeedbackMessageProvider
{

	/**
	 * @throws \ZxcvbnPhp\Exception\FeedbackMessageNotFoundException
	 */
	public function getMessage(string $key): string;
}
