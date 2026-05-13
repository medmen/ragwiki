# ragwiki 

ragewiki is a small tool mostly written in PHP to add AI seaerch functionality to a dokuiki instance.
Basically it reads all wiki pages, extracts meaningfull context and adds knowledge to a pretrained ai model.
This model is used by a search frontend to answer questions about th dokuwiki content.

## Installation:

1. Install PHP  
    You should have done that already
2. Install SQLITE as database  
    for Ubuntu linux this would be `sudo apt install sqlite3`
3. Install dependencies  
   run `composer install` in Terminal
4. Configure ragwiki  
    edit config.php, e.g. adjust the path to your /dokuwiki/data/pages directory
5. Setup the database  
   in terminal run `php create_database.php`
6. run ingestion  
    the tool needs to read dokuwiki pages, chop them into chunks just large enough  
for the model to make sense and then transform those chunks into vectors  
called "embeddings" for similarity search.  
in terminal run `php ingestion.php` to do so, rerun this script every time the pages change.  
you might want to employ a cron-job or similar thing to automate this.
7. point your browser to index.php  
to use the AI based search. Feel free to import the seach mask wherever you like.  

### Found a bug?
feel free to file a report at my github repo

### Like this?
great, leave me a star or buy me a coffee

### Want to improve?
very great, fork the repo, add your changes and commit 