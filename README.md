phing-tasks
===========

Another my tasks for phing build system.


## Validations tasks ##
Task for validate dir or properties requiring in targets.


## Schema-manage tasks ##
Tasks for using [schema-manage](http://taco-beru.name/schema-manage). Tool for versioning database schema.


## Mercurial tasks ##
Task for using mercurial versioning tool.


## Using ##
Add to your phing build.xml definition of class paths:


    <path id="project.class.path">
      <pathelement dir="${project.basedir}/vendor/tacoberu/phing-tasks/source/main"/>
    </path>




And link to using tasks. Example this:


    <taskdef name="validation.assert" classname="tasks.taco.validation.ValidationAssertTask" classpathref="project.class.path"/>
    <taskdef name="schemamanage.update" classname="tasks.taco.schemamanage.SchemaManageUpdateTask" classpathref="project.class.path"/>
    <taskdef name="schemamanage.status" classname="tasks.taco.schemamanage.SchemaManageStatusTask" classpathref="project.class.path"/>
