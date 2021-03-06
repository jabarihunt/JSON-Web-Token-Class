<?php namespace jabarihunt;

    /********************************************************************************
    * JSON Web Token Handler | PHP 5.6+
    *
    * A simple class for both signing and verifying JSON WEb Tokens.  Additionally,
    * a method is provided for generating secrets.  It currently supports HS256,
    * HS384, and HS512, but eventually support for the corresponding RS and ES
    * algorithms will be added!
    *
    * JWT::generateSecret() generates a secret with twice the recommended key length.
    *
    * @author Jabari J. Hunt <jabari@jabari.net>
    * @todo Add support for ES and RS algorithms
    ********************************************************************************/

    class JWT
    {
        /********************************************************************************
         * CLASS VARIABLES
         * @var string ALGORITHM_HS256
         * @var string ALGORITHM_HS384
         * @var string ALGORITHM_HS512
         * @var string ALGORITHM_NONE
         * @var array ALGORITHMS
         * @var array USES_KEY
         * @var array USES_SECRET
         ********************************************************************************/

            const ALGORITHM_HS256 = 'HS256';
            const ALGORITHM_HS384 = 'HS384';
            const ALGORITHM_HS512 = 'HS512';
            const ALGORITHM_NONE  = 'none';

            const ALGORITHMS =
            [
                'HS256' => ['name' => 'HS256', 'sha' => 'sha256', 'length' => '64'],
                'HS384' => ['name' => 'HS384', 'sha' => 'sha384', 'length' => '96'],
                'HS512' => ['name' => 'HS512', 'sha' => 'sha512', 'length' => '128'],
                'none'  => ['name' => 'none', 'sha' => NULL, 'length' => NULL]
            ];

            const USES_KEY    = [];
            const USES_SECRET = ['HS256', 'HS384', 'HS512'];

        //////////////////////////////////////////////
        // PUBLIC METHODS
        //////////////////////////////////////////////

            /********************************************************************************
             * SIGN METHOD
             * @param array $payload
             * @param string $secretOrPrivateKey
             * @param string $algorithm
             * @throws \Exception Invalid algorithm passed to JWT::sign().
             * @return string
             ********************************************************************************/

                final public static function sign(Array $payload, $secretOrPrivateKey, $algorithm = self::ALGORITHM_HS256)
                {
                    // MAKE SURE A SUPPORTED ALGORITHM WAS PASSED

                        if (array_key_exists($algorithm, self::ALGORITHMS)) {$algorithm = self::ALGORITHMS[$algorithm];}
                        else {throw new \Exception('Invalid algorithm passed to JWT::sign().');}

                    // BUILD TOKEN HEADER AND PAYLOAD

                        $token  = self::encode(['alg' => $algorithm['name'], 'type' => 'JWT']);
                        $token .= '.' . self::encode($payload);

                    // SIGN THE TOKEN

                        if (in_array($algorithm['name'], self::USES_SECRET))
                        {
                            $token .= '.' . self::encode(hash_hmac($algorithm['sha'], $token, $secretOrPrivateKey), FALSE);
                        }
                        else if (in_array($algorithm['name'], self::USES_KEY)) {}
                        else {$token .= '.';}

                    // RETURN THE TOKEN

                        return $token;
                }

            /********************************************************************************
             * VERIFY METHOD
             * @param string $token
             * @param string $secretOrPublicKey
             * @throws \Exception Invalid token passed to JWT::verify()
             * @throws \Exception Token signed with unsupported algorithm.
             * @return array
             ********************************************************************************/

                final public static function verify($token, $secretOrPublicKey)
                {
                    // SET INITIAL VARIABLES | MAKE SURE REQUIRED PARTS EXIST

                        $data['isVerified'] = FALSE;
                        $data['header']     = [];
                        $data['payload']    = [];

                        list($header, $payload, $signature) = explode('.', $token);

                        if (!empty($header) && !empty($payload))
                        {
                            // EXTRACT HEADER AND PAYLOAD

                                $data['header']  = self::decode($header);
                                $data['payload'] = self::decode($payload);

                            // VALIDATE SIGNATURE

                                if (!empty($data['header']['alg']) && $data['header']['alg'] !== 'none')
                                {
                                    // GET ALGORITHM

                                        if (!empty(self::ALGORITHMS[$data['header']['alg']])) {$algorithm = self::ALGORITHMS[$data['header']['alg']];}
                                        else {throw new \Exception('Token signed with unsupported algorithm.');}

                                    // HS256, HS384, HS512 -> CHECK SIGNATURE

                                        if
                                        (
                                            in_array($algorithm['name'], self::USES_SECRET) &&
                                            $signature === self::encode(hash_hmac($algorithm['sha'], "{$header}.{$payload}", $secretOrPublicKey), FALSE)
                                        )
                                        {$data['isVerified'] = TRUE;}

                                    // ES256, ES384, ES512, RS256, RS384, RS512 -> CHECK SIGNATURE

                                        else if (in_array($algorithm['name'], self::USES_KEY)) {}
                                }

                        }
                        else {throw new \Exception('Invalid token passed to JWT::verify().');}

                    // RETURN DATA

                        return $data;
                }

            /********************************************************************************
             * GENERATE SECRET METHOD
             * @param string $algorithm
             * @throws \Exception
             * @return string
             ********************************************************************************/

                final public static function generateSecret($algorithm = self::ALGORITHM_HS256)
                {
                    if (array_key_exists($algorithm, self::ALGORITHMS)) {$algorithm = self::ALGORITHMS[$algorithm];}
                    else {throw new \Exception('Invalid algorithm passed to JWT::generateSecret().');}

                    return base64_encode(random_bytes($algorithm['length']));
                }

        //////////////////////////////////////////////
        // PRIVATE METHODS
        //////////////////////////////////////////////

            /********************************************************************************
             * ENCODE METHOD
             * @param mixed $data
             * @param boolean $encodeJSON
             * @return string
             ********************************************************************************/

                private static function encode($data, $encodeJSON = TRUE)
                {
                    if ($encodeJSON) {$data = json_encode($data);}
                    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
                }

            /********************************************************************************
             * DECODE METHOD
             * @param string $data
             * @param boolean $decodeJSON
             * @return array
             ********************************************************************************/

                private static function decode($data, $decodeJSON = TRUE)
                {
                    $data = base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
                    if ($decodeJSON) {$data = json_decode($data, TRUE);}
                    return $data;
                }
    }

?>