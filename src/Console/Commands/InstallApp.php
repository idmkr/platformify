<?php

namespace Idmkr\Platformify\Console\Commands;


use Config;
use Platform;
use Schema;
use Platform\Installer\Console\InstallCommand;

class InstallApp extends InstallCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'app:install {--seed} {--seed-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the app for testing';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if($this->option('seed-only')) {
            $this->seedApp();
            return;
        }

        // Show the welcome message
        $this->showWelcomeMessage();

        $this->resetDB();

        $this->migrate();

        $this->setUpPlatform();

        $this->comment('Installation complete :)');
    }

    /**
     * Seed a given database connection.
     *
     * @param  string  $class
     * @return void
     */
    public function seedApp($class = 'DatabaseSeeder')
    {
        $this->call('db:seed', ['--class' => $class]);

        // Onevalue custom seeds
        $this->call('db:seed', ['--class' => 'ConfigTableSeeder']);
        $this->call('db:seed', ['--class' => 'MediaTableSeeder']);
    }

    /**
     * Install the migration table.
     *
     * @return void
     */
    protected function migrate()
    {
        $this->call('migrate:install', ['--env' => $this->getEnv()]);
    }

    /**
     * Setup platform.
     */
    protected function setUpPlatform()
    {
        // Sqlite is the default but can be changed
        $connection = Config::get('database.default');
        // Get database config
        $config = Config::get("database.connections.".$connection);

        // Set database config
        $this->installer->setDatabaseData($connection, $config);
        $this->installer->setUserData([
            'email'            => 'team@idmkr.io',
            'password'         => 'crif9bf1',
            'password_confirm' => 'crif9bf1',
        ]);

        // Migrate packages
        // Always pass true because we handle the testing stuff ourselves
        $this->installer->install(true);

        // Real app installation ( no testing )
        // This block code is because we need to get rid of
        // the annoying and highly useless $this->installer->setupDatabase()
        if(!$this->isTestEnv()) {
            // Disable checkpoints
            unset($this->getLaravel()['sentinel.checkpoints']);
            // Set install flag to true
            $this->installer->updatePlatformInstalledVersion($this->getLaravel()['platform']->codebaseVersion());
        }

        // Migrate application.
        $this->call('migrate', ['--env' => $this->getEnv()]);

        if($this->option('seed'))
            $this->seedApp();

        // Boot extensions
        Platform::bootExtensions();
    }

    /**
     * Resets the database
     *
     * @return void
     */
    protected function resetDB()
    {
        $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tableNames as $table) {
            Schema::drop($table);
        }
    }

    /**
     * Get the chosen or default env
     *
     * @return string
     */
    protected function getEnv()
    {
        return $this->option('env') ?: getenv('APP_ENV');
    }

    /**
     * Returns true if this is a testing environment
     * Does NOT mean APP_ENV equals "testing"
     * This is due to the Platform->isInstalled() method
     * which does not permit installing in console context, with testing activated, without APP_ENV == testing
     *
     * @return string
     */
    protected function isTestEnv()
    {
        return getenv('DB_CONNECTION') == 'sqlite';
    }

    /**
     * Shows the welcome message.
     *
     * @return void
     */
    protected function showWelcomeMessage()
    {
        $this->output->writeln(<<<WELCOME
<fg=white>

     .:+syyyyyyo/-`                                                           
  -oyyyyyyyyyyyyyy+.                                   /ooo.         /hhh/   
 +yyyy+-`:yyys:oyyyy:                                 -ssyy.         +hhho   
+yyyo.   -yyyy//syyyy///:.            `````   ```    `syyy/``..```.` +hhho ``` `....--....-----..`  
yyyy.     -ossssssyyysssss::+++/-`  ./++++/:`:++o-   /yyyhsyyhhyyhhy-+hhho-yyys`  -syys .+syyys+.   
yyyy.       `````+yyy+:sssso+oooo+`.+oo::ooo:oooss- .yyyyhdhs++yhhhh:+hhh+:hhhh`  :hhhh.yhho/yyyo   
+yyys.          :yyyy.-ssso` `+ooo:-ooo-/ooo.ossyyy.oyyyhdd+   `hhhh:+hhh+:hhhh`  :hhhh.hhy++yyy/   
 +yyyy+-`   `.:oyyyy: -ooo+   +ooo:-ooo+oo+.`syyyyyyyyyhddh+   `hhhh:+hhh+:hhhh.  /hhhh.yyyyyyy:    
  -oyyyyyyssyyyyyy+`  -ooo+   :ooo+/oooooo//syyysyyyyyy:yhhhs+oyhhhhsyhhhyshhhhyosyhhhy+yyyyyyo+sys`
    `:+syyyyyso/-`    .+oo/    -/oooo+/+syyyys+. :yyys:  :oyhhhhs+yhhhhyhhhhsoshhhysoyyyys+syyyyyo/`
    
    
                                          ,ad8888ba,   88  
                                         d8"'    `"8b  88  
                                        d8'            88  
                                        88             88  
                                        88             88  
                                        Y8,            88  
                                         Y8a.    .a8P  88  
                                          `"Y8888Y"'   88  
</fg=white>
WELCOME
        );
    }

}
