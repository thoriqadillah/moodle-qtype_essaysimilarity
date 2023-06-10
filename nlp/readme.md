# Natural Language Processing
This folder contains the function that needed to perform similarity algorithm.

Part of the implementation of the folder came from many implementation that already out there, some of it is rewrite. For example, cosine_similarity.php is rewrite from [this function of this library](https://github.com/angeloskath/php-nlp-tools/blob/master/src/NlpTools/Similarity/CosineSimilarity.php). This is because I have no idea how to make class with psr-4 that actually works when imported with `use` keyword. So, I rewrite it and import it with `require_once`. But, this folder itself is not a third party library.

Credit to 
- @angeloskath, author of [PHP NlpTools](https://github.com/angeloskath/php-nlp-tools/). This library is used to implement cosine similarity, tokenizer, and english stemmer
- @jorgecasas, author of [PHP-ML](https://github.com/jorgecasas/php-ml). This library is used to implement TF-IDF
- @fiji, author of [JAMA](https://github.com/fiji/Jama). This library used as source of SVD implementation

