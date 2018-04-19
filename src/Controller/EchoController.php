<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle\Controller;

use Railt\Http\InputInterface;

/**
 * @internal Caution: This is an example controller, do not use it in production!
 */
class EchoController
{
    /**
     * @param InputInterface $input
     * @return string
     */
    public function say(InputInterface $input): string
    {
        $result = 'Your message is: ' . $input->get('message');

        return $input->get('upper') ? \mb_strtoupper($result) : $result;
    }
}
