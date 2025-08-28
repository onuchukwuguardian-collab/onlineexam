# Bulk Upload Questions - Instructions

## Overview
The bulk upload feature allows you to import multiple questions at once using a CSV (Comma-Separated Values) file. This is much faster than creating questions one by one.

## CSV File Format Requirements

### Required Columns (Must be present)
1. **question_text** - The question text (up to 65,000 characters)
2. **correct_answer** - The correct answer letter (A, B, C, D, or E)
3. **option_a_text** - Text for option A (required)
4. **option_b_text** - Text for option B (required)

### Optional Columns
5. **option_c_text** - Text for option C (optional)
6. **option_d_text** - Text for option D (optional)
7. **option_e_text** - Text for option E (optional)
8. **image_filename** - Name of image file (optional, not currently implemented)

## Important Rules

### Column Headers
- Headers are **case-insensitive** (question_text = Question_Text = QUESTION_TEXT)
- Headers must be in the **first row** of your CSV file
- Column order doesn't matter, but all required columns must be present

### Data Requirements
- **Question text**: Cannot be empty, maximum 65,000 characters
- **Correct answer**: Must be A, B, C, D, or E (case-insensitive)
- **Options**: At least options A and B must have text
- **Option text**: Maximum 1,000 characters each
- **Correct answer must match**: The correct_answer letter must correspond to an option that has text

### CSV Formatting
- Use commas (,) to separate columns
- Wrap text in quotes ("") if it contains commas, quotes, or line breaks
- Use double quotes ("") to include a quote character in text
- Save file with .csv extension
- Maximum file size: 2MB

## Step-by-Step Instructions

### 1. Download Template
- Download the sample template: `questions_bulk_upload_template.csv`
- This shows the exact format needed

### 2. Prepare Your Data
- Open the template in Excel, Google Sheets, or any spreadsheet program
- Replace the sample data with your questions
- Ensure each row has:
  - A complete question
  - The correct answer letter (A-E)
  - Text for at least options A and B
  - Correct answer matches an option with text

### 3. Save as CSV
- In Excel: File → Save As → CSV (Comma delimited)
- In Google Sheets: File → Download → Comma-separated values (.csv)
- Ensure the file has a .csv extension

### 4. Upload
- Go to the Questions page for your subject
- Click "Bulk Upload" button
- Select your CSV file
- Click "Upload and Process Questions"

## Example Data

```csv
question_text,correct_answer,option_a_text,option_b_text,option_c_text,option_d_text,option_e_text
"What is the capital of France?",A,"Paris","London","Berlin","Madrid","Rome"
"Which planet is known as the Red Planet?",B,"Venus","Mars","Jupiter","Saturn","Mercury"
"What is 2 + 2?",A,"4","3","5","6","7"
```

## Common Errors and Solutions

### "CSV header mismatch"
- **Problem**: Missing required columns or incorrect column names
- **Solution**: Ensure your CSV has question_text, correct_answer, option_a_text, option_b_text columns

### "Correct answer letter 'X' is not among the provided option letters"
- **Problem**: Correct answer doesn't match any option with text
- **Solution**: If correct_answer is "C", make sure option_c_text has content

### "At least two valid options are required"
- **Problem**: Less than 2 options have text
- **Solution**: Ensure at least option_a_text and option_b_text have content

### "Column count mismatch"
- **Problem**: Some rows have different number of columns
- **Solution**: Check for missing commas or extra commas in your data

### "Question text is required"
- **Problem**: Empty question_text field
- **Solution**: Every row must have question text

## Tips for Success

1. **Start Small**: Test with 2-3 questions first
2. **Check Your Data**: Review each row before uploading
3. **Use Quotes**: Wrap text in quotes if it contains commas or special characters
4. **Backup**: Keep a copy of your original data
5. **Validate**: The system will show detailed error messages for any problems

## Character Limits
- Question text: 65,000 characters maximum
- Option text: 1,000 characters maximum each
- File size: 2MB maximum

## Supported File Types
- .csv (Comma-separated values)
- .txt (Plain text with comma separation)

## After Upload
- Successfully imported questions will appear in your questions list
- Any errors will be displayed with specific row numbers
- You can edit individual questions after import if needed

## Need Help?
If you encounter issues:
1. Check the error messages - they're specific and helpful
2. Verify your CSV format matches the template
3. Test with a smaller file first
4. Contact support if problems persist

---
**Remember**: The bulk upload is designed to save time, but accuracy in your CSV format is crucial for success!