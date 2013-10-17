PHP-auth
========

A small authentication library using bCrypt and session checking 

Use
-------
By instantiating the class on a page you'll automatically be checking the session against the database. Define your login form in login.php and reference in the class options.  By flipping the encodable perameter to true you'll be able to generate your hashed passwords.

The Repo includes the sql for the basic user table with test user: 
U:test@test.com
P:password 
