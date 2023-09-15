#!/bin/bash
# deploy.sh

# This script is used for building a package for the "code-injection" WordPress plugin.
# The purpose of this script is to prepare the plugin for installation on a WordPress website.
# While "git push origin master" pushes the entire codebase to GitHub, this script creates a bundle specifically for the WordPress SVN repository.

deploy() {
  local work_tree=$1

  # Run the git command for deployment
  export GIT_WORK_TREE=$work_tree
  git checkout -f

  # Delete files in .gitignore.deploy from working directory
  while IFS= read -r line
  do
    # Ignore comments and empty lines
    [[ "$line" =~ ^#.*$ || "$line" == "" ]] && continue
    
    # Check if file exists before deleting
    if [ -e "$work_tree/$line" ]; then
      rm $work_tree/$line
    elif [[ "$line" == *"*"* ]]; then
      # Handle patterns with wildcards
      find $work_tree -path "$line" -delete
    else
      echo "File $work_tree/$line does not exist"
    fi
  done < ".gitignore.deploy"
}

deploy ../code-injection