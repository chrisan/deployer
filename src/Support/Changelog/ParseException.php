<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class ParseException extends \Exception
{
    public function __construct(string $message = '', string $code = '')
    {
        parent::__construct("$message\n\n{$code}\n\n");
    }
}
