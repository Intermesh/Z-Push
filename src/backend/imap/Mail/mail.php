<?php
/**
 * internal PHP-mail() implementation of the PEAR Mail:: interface.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2010-2017, Chuck Hagenbuch
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    Mail
 * @package     Mail
 * @author      Chuck Hagenbuch <chuck@horde.org> 
 * @copyright   2010-2017 Chuck Hagenbuch
 * @license     http://opensource.org/licenses/BSD-3-Clause New BSD License
 * @version     CVS: $Id$
 * @link        http://pear.php.net/package/Mail/
 */

 /**
 * Z-Push changes
 *
 * removed PEAR dependency by implementing own raiseError()
 *
 * Reference implementation used:
 * https://github.com/pear/Mail/tree/v2.0.0
 *
 *
 */
 
/**
 * internal PHP-mail() implementation of the PEAR Mail:: interface.
 * @package Mail
 * @version $Revision$
 */
class Mail_mail extends Mail {

    /**
     * Any arguments to pass to the mail() function.
     * @var string
     */
    var $_params = '';

    /**
     * Constructor.
     *
     * Instantiates a new Mail_mail:: object based on the parameters
     * passed in.
     *
     * @param array $params Extra arguments for the mail() function.
     */
    public function __construct($params = null)
    {
        // The other mail implementations accept parameters as arrays.
        // In the interest of being consistent, explode an array into
        // a string of parameter arguments.
        if (is_array($params)) {
            $this->_params = join(' ', $params);
        } else {
            $this->_params = $params;
        }
    }

    /**
     * Implements Mail_mail::send() function using php's built-in mail()
     * command.
     *
     * @param mixed $recipients Either a comma-seperated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     *
     * @param array $headers The array of headers to send with the mail, in an
     *              associative array, where the array key is the
     *              header name (ie, 'Subject'), and the array value
     *              is the header value (ie, 'test'). The header
     *              produced from those values would be 'Subject:
     *              test'.
     *
     * @param string $body The full text of the message body, including any
     *               Mime parts, etc.
     *
     * @return mixed Returns true on success, or a PEAR_Error
     *               containing a descriptive error message on
     *               failure.
     */
    public function send($recipients, $headers, $body)
    {
        if (!is_array($headers)) {
            // Z-Push change: rasiseError dependancy
            return Mail_mail::raiseError('$headers must be an array');
        }

        $result = $this->_sanitizeHeaders($headers);
        // Z-Push change: rasiseError dependancy
        //if (is_a($result, 'PEAR_Error')) {
        if ($result === false) {
            return $result;
        }

        // If we're passed an array of recipients, implode it.
        if (is_array($recipients)) {
            $recipients = implode(', ', $recipients);
        }

        // Get the Subject out of the headers array so that we can
        // pass it as a seperate argument to mail().
        $subject = '';
        if (isset($headers['Subject'])) {
            $subject = $headers['Subject'];
            unset($headers['Subject']);
        }

        // Also remove the To: header.  The mail() function will add its own
        // To: header based on the contents of $recipients.
        unset($headers['To']);

        // Flatten the headers out.
        $headerElements = $this->prepareHeaders($headers);
        // Z-Push change: rasiseError dependancy
        //if (is_a($headerElements, 'PEAR_Error')) {
        if ($headerElements === false) {
            return $headerElements;
        }
        list(, $text_headers) = $headerElements;

        // We only use mail()'s optional fifth parameter if the additional
        // parameters have been provided and we're not running in safe mode.
        if (empty($this->_params) || ini_get('safe_mode')) {
            $result = mail($recipients, $subject, $body, $text_headers);
        } else {
            $result = mail($recipients, $subject, $body, $text_headers,
                           $this->_params);
        }

        // If the mail() function returned failure, we need to create a
        // PEAR_Error object and return it instead of the boolean result.
        if ($result === false) {
            // Z-Push change: rasiseError dependancy
            $result = Mail_mail::raiseError('mail() returned failure');
        }

        return $result;
    }

    /**
     * Z-Push helper for error logging
     * removing PEAR dependency
     *
     * @param  string  debug message
     * @return boolean always false as there was an error
     * @access private
     */
    static function raiseError($message) {
        ZLog::Write(LOGLEVEL_ERROR, "Mail<mail> error: ". $message);
        return false;
    }
}
