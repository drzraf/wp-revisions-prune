((php-mode . (
	      (eval . (set (make-local-variable 'my-project-path) (expand-file-name (locate-dominating-file default-directory ".dir-locals.el"))))
              (flycheck-phpcs-standard-dir . my-project-path)
              (eval . (setq flycheck-phpcs-standard (concat my-project-path "phpcs.xml.dist")))
              )))
