function getRecaptchaToken(publicKey, successGettingToken, errorGettingToken) {
    grecaptcha.ready(function() {
        grecaptcha.execute(publicKey, {action: 'submit'})
            .then(successGettingToken)
            .catch(errorGettingToken);
    });
}