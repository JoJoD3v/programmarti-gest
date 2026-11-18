<?php

namespace App\Ticketing\Support;

use RuntimeException;

/**
 * The LLM did not answer usefully (no key, timeout, 429, 5xx, malformed output): callers degrade, never lose the ticket.
 */
class LlmUnavailableException extends RuntimeException {}
