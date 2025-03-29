#!/bin/bash
# debug_script.sh
JOB_ID="test_job"
echo "Creating test files..."
echo "10" > "tmp/${JOB_ID}_progress.txt"
echo "Clustal log content" > "tmp/${JOB_ID}_clustalo.log"
echo "Plotcon log content" > "tmp/${JOB_ID}_plotcon.log"
echo "Files created:"
ls -l tmp/${JOB_ID}_*
