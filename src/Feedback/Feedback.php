<?php declare(strict_types = 1);

namespace ZxcvbnPhp\Feedback;

class Feedback
{

	/**
	 * @var ?string
	 */
	private $warning;

	/**
	 * @var array
	 */
	private $suggestions;

	public function __construct(
		?string $warning,
		array $suggestions
	)
	{
		$this->warning = $warning;
		$this->suggestions = $suggestions;
	}


	public function getWarning(): ?string
	{
		return $this->warning;
	}


	public function getSuggestions(): array
	{
		return $this->suggestions;
	}
}
