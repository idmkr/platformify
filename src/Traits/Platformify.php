<?php namespace Idmkr\Platformify\Traits;

use Session;

trait Platformify {

    protected $countRedirect = 0;


    public function boot()
    {
        $this->app = $this->getModule('Laravel5')->app;
        $this->artisan('app:install', ['--env' => 'testing']);
        //$this->resetSession();
    }


    // HOOK: before each step
    public function _beforeStep(\Codeception\Step $step) {
        //Always Save the session before changing page
        if(in_array($step->getAction(), ['click', 'amOnPage'])){
            if($this->countRedirect >0)
                Session::save();
            $this->countRedirect ++;
        }
    }

    public function getConnectedUser(){
        $userService = $this->getModule('Laravel5')->grabService('platform.users');
        return  $userService->getSentinel()->check();
    }

    public function resetSession(){
        Session::regenerate();
    }


    public function amLoggedAs($user, $driver = null)
    {
        $userService = $this->getModule('Laravel5')->grabService('platform.users');
        // Register the user
        list($messages) = $userService->auth()->login($user);
        // Do we have any errors?
        if (!$messages) {
            return;
        }
        $this->fail(json_encode($messages));
    }


    public function dontSeePlatformErrors(){
        $errors = [];
        $this->debug('Checking For Errors');
        if(empty(Session::get('cartalyst.alerts')))
            Session::put('cartalyst.alerts', Session::get('cartalyst.alerts_old'));

        if(!empty(Session::get('cartalyst.alerts'))) {
            $errorService = $this->getModule('Laravel5')->grabService('alerts');
            $errors = $errorService->whereType('error')->get();
        }

        if (count($errors) > 0) {
            $this->fail(json_encode( $errors ));
            $this->debugSection('Platform Errors', $errors);
        }else{
            $this->debug('No Error');
        }
        return;
    }


    public function debug($message)
    {
        parent::debug($message);
    }

    public function debugSection($title, $message)
    {
        parent::debugSection($title, $message);
    }

    public function getModule($name)
    {
        return parent::getModule($name);
    }

    /**
     * Call artisan command and return code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        return $this->code = $this->app['Illuminate\Contracts\Console\Kernel']->call($command, $parameters);
    }
}