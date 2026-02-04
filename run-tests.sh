#!/bin/bash

echo "🚀 Starting comprehensive test suite..."
echo ""

# Step 1: Rebuild test database
echo "📊 Rebuilding test database..."
composer run rebuild-testdb
if [ $? -ne 0 ]; then
    echo "❌ Failed to rebuild test database"
    exit 1
fi
echo "✅ Test database rebuilt successfully"
echo ""

# Step 2: Check code style
echo "🔍 Checking code style..."
composer run cs-check
if [ $? -ne 0 ]; then
    echo "⚠️  Code style issues found. Attempting to fix..."
    composer run cs-fix
    if [ $? -ne 0 ]; then
        echo "❌ Failed to fix code style issues"
        exit 1
    fi
    echo "✅ Code style fixed successfully"
else
    echo "✅ Code style is clean"
fi
echo ""

# Step 3: Run PHPUnit tests
echo "🧪 Running PHPUnit tests..."
php bin/phpunit --no-coverage
if [ $? -ne 0 ]; then
    echo "❌ Tests failed"
    exit 1
fi

echo ""
echo "🎉 All tests passed successfully!"
echo "✅ Database rebuilt"
echo "✅ Code style checked/fixed"
echo "✅ PHPUnit tests passed" 