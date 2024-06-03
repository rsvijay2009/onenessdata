### Steps to be followed in production server

1. Login into production server and open the terminal

2. Navigate to the current project directory
    
    ```
    cd /var/www/onenessdata.com/staging_new
    ```
    
3. Delete the existing project folder

    ```
    rm -rf onenessdata
    ```

4. Clone the latest version from Github

    ```
    git clone https://github.com/rsvijay2009/onenessdata.git
    ```

5. Give 777 permission for the uploads folder

    ```
    cd onenessdata/src
      
    chmod -R 777 uploads
    ```
6. Upload the database.php file with correct credentials

7. Run the command to install all the required packages

    ```
    composer install
    ```


data_quality_dimensions_stats

Need to create a column percentage in top and bottom 5



Create two drop down to shows the columns and create a another one to choose the relationship for join(Select multiple columns)


After join redirect to another page and show the result

Save the table only if the user like to save(Display it in the left panel under join option)