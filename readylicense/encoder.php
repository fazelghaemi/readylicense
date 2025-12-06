<?php

$encodedCode = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST["code"])) {
        $encoder = new ByteScrambler();
        $encoder->SetCode($_POST["code"]);
        $encodedCode = $encoder->GetEncodedCode();
    } else {
        $encodedCode = "لطفاً کد خود را وارد کنید.";
    }
    echo $encodedCode;
}
class ByteScrambler
{
    private $_code;
    private $secretKey;
    private $iv;
    const NEWLINE = "\n";
    public function SetCode($code)
    {
        $this->_code = $code;
        $this->secretKey = bin2hex(random_bytes(16));
        $this->iv = bin2hex(random_bytes(8));
    }
    private function clean($string)
    {
        $find = ["<?php", "?>"];
        $replace = ["", ""];
        return str_replace($find, $replace, $string);
    }
    private function encrypt($code)
    {
        return openssl_encrypt($code, "AES-256-CBC", $this->secretKey, 0, $this->iv);
    }
    private function addCodeTags($code)
    {
        return "<?php\n" . $code . "\n?>";
    }
    public function GetEncodedCode()
    {
        $code = $this->clean($this->_code);
        $encryptedCode = $this->encrypt($code);
        $evalCode = "eval(openssl_decrypt(\"" . $encryptedCode . "\", \"AES-256-CBC\", \"" . $this->secretKey . "\", 0, \"" . $this->iv . "\"));";
        $finalCode = $this->addCodeTags($evalCode);
        return $finalCode;
    }
}

?>