<?php

class Errepl {

    const REPL_PROVIDER_PSYSH = 'psysh';
    const REPL_PROVIDER_BORIS = 'boris';
    const REPL_PROVIDER_DEFAULT = self::REPL_PROVIDER_PSYSH;

    const ERR_TYPE_ERROR = 'error';
    const ERR_TYPE_EXCEPTION = 'exception';

    /* properties are public to allow external hacking */
    public $replProvider = null;
    public $vars = array();


    /**
     * @var string $replProvider repl provider identifier, allowed values: 'psysh','boris'
     */
    public function __construct($replProvider = false)
    {
        $this->replProvider = $replProvider ? $replProvider : self::REPL_PROVIDER_DEFAULT;
        
        $this->updateErrHandlers();
    }


    public function updateErrHandlers()
    {
        set_error_handler(array($this, 'errorHandler'));//, $error_reporting = E_ALL );
        set_exception_handler(array($this, 'exceptionHandler'));
    }


    public function restoreErrHandlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }


    /**
     * @var string $key name of the variable for debugging
     * @var mixed $value content of the variable for debugging
     */
    public function addVar($key, $value)
    {
        $this->vars[$key] = $value;
    }


    /**
     * @var array[] $vars variables for debugging in key-value pair format
     */
    public function addVars(array $vars)
    {
        $this->vars = array_merge($this->vars,$vars);
    }


    /**
     * @var Exception $exception the captured exception
     */
    public function exceptionHandler ($exception)
    {
        $this->addVar(self::ERR_TYPE_EXCEPTION,$exception);

        $this->repl(self::ERR_TYPE_EXCEPTION);

        $this->restoreErrHandlers();
    }


    /**
     * @var int $errno the error number
     * @var string $errstr the error message
     * @var string $errfile the error file
     * @var int $errline the line of the error
     */
    public function errorHandler ($errno, $errstr, $errfile, $errline, $errcontext)
    {
        // error could be converted to exception but $errcontext would be lost
        //throw new ErrorException($errstr, 0, $errno, $errfile, $errline);

        $error = array();
        $error['errno'] = $errno;

        $this->addVar(self::ERR_TYPE_ERROR, $error);
        $this->addVar('ctxt', $errcontext);

        $this->repl(self::ERR_TYPE_ERROR);

        $this->restoreErrHandlers();
    }


    // @todo may interest to catch PHP Fatal Error?
    // register_shutdown_function


    public function getTrace()
    {
        $trace = debug_backtrace();
        array_shift($trace); // remove the stack about this handler

        return $trace;
    }


    /**
     * @var string $replProvider repl provider identifier
     * @var string $message explanatory message of the error/exception
     */
    public function repl ($replProvider, $message = '')
    {
        echo 'Errepl launched by '.$replProvider;

        switch($this->replProvider) {
            case self::REPL_PROVIDER_PSYSH:

                \Psy\Shell::debug($this->vars);
                break;

            case self::REPL_PROVIDER_BORIS:

                $boris = new \Boris\Boris('errepl> ');
                $boris->setLocal($this->vars);
                $boris->start();
                break;
        }
    }

}
?>
