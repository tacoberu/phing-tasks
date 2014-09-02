phing-tasks
===========

Another my tasks for [phing build system](http://www.phing.info/).


## Using ##
Add to your phing build.xml definition of class paths:

    <path id="project.class.path">
      <pathelement dir="${project.basedir}/vendor/tacoberu/phing-tasks/source/main"/>
    </path>


And link to using tasks. Example this:

    <taskdef name="validation.assert" classname="tasks.taco.validation.ValidationAssertTask" classpathref="project.class.path"/>
    <taskdef name="schemamanage.update" classname="tasks.taco.schemamanage.SchemaManageUpdateTask" classpathref="project.class.path"/>
    <taskdef name="schemamanage.status" classname="tasks.taco.schemamanage.SchemaManageStatusTask" classpathref="project.class.path"/>

or:

    <taskdef file="${project.basedir}/vendor/tacoberu/phing-tasks/source/main/tasks/tasks.properties"
    		classpath="${project.basedir}/vendor/tacoberu/phing-tasks/source/main"
    		/>


## Logger ##
### ChameleonLogger ###
Umožňuje vypisovat do více cílů a s různě podrobně.

    phing -logger vendor.tacoberu.phing-tasks.source.main.listener.ChameleonLogger -Dchameleon.html.log=log.html -Dchameleon.html.error=error.html -Dchameleon.html.level=4

#### Popis ####

    -Dchameleon.html.log=<soubor s logem>

Soubor, kam se logují provozní informace ve formátu html.

    -Dchameleon.html.error=<soubor s chybovkama>

Soubor, kam se logují chyby ve formátu html.

    -Dchameleon.html.level=1

Úroveň podrobnosti výpisu.

* level=4 - Debug
* level=3 - Verbose
* level=2 - Info
* level=1 - Warning
* level=0 - Errors


## Tasks ##

### taco.autoload ###
Cooperate with [Composer](https://getcomposer.org/) autoload.

    <taco.autoload file="${dir.source.vendors}/autoload.php"/>


### taco.typedef ###
Added functionalite for load types by properties definition.

    <typedef file="${project.basedir}/source/main/types/types.properties"
    		classpath="${project.basedir}/source/main"
    		/>


### taco.exec ###
Doc comming-soon.

### taco.exists ###
Doc comming-soon.

### taco.loadfile ###
Doc comming-soon.

### taco.merge ###
Doc comming-soon.

### taco.require ###
Doc comming-soon.

### taco.symlink ###
Doc comming-soon.

### taco.sync ###
Doc comming-soon.


## Tasks for Schema-manage ##
Tasks for using [schema-manage](http://taco-beru.name/schema-manage). Tool for versioning database schema.

### schemamanage.createorupdate ###

### schemamanage.status ###

### schemamanage.update ###


## Tasks for core validation ##
Task for validate dir or properties requiring in targets.

### validation.assert ###


## Tasks for Mercurial ##
Task for using mercurial versioning tool.

### hg.tags ###

    <hg.tags repository="${project.basedir}/repos/une" output="true">
    	<arg name="v" />
    </hg.tags>


### hg.branches ###

    <hg.branches repository="${project.basedir}/repos/une" output="true">
    	<arg name="v" />
    </hg.branches>


### hg.update ###

    <hg.update repository="${project.basedir}/repos/une" output="true" branch="default">
    	<arg name="clean" />
    </hg.update>


### hg.push ###

    <hg.push repository="${project.basedir}/repos/une" output="true" remote="testx" outputProperty="hg.push.msg" >
    	<arg name="new-branch"/>
    	<arg name="-f"/>
    </hg.push>


### hg.status ###

    <hg.status repository="${project.basedir}/repos/une" output="true" />


### hg.assert-clean ###
Throw exception if is in repository uncommitled changes.

    <hg.assert-clean repository="${project.basedir}/repos/une">V repozitáři jsou nějaké změny.</hg.assert-clean>




## Tasks for i18n ##
Tasky for internationalize. Parse a merge messages.

### gettext-scan ###
Doc comming-soon.

### gettext-merge ###
Doc comming-soon.

### gettext-compile ###
Doc comming-soon.
