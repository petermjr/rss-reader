import React, { useState } from 'react';
import { Form, Button, Card, Alert } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

function AddFeed() {
  const [urls, setUrls] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [results, setResults] = useState(null);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setResults(null);

    // Split URLs by newline and filter out empty lines
    const urlList = urls.split('\n')
      .map(url => url.trim())
      .filter(url => url.length > 0);

    if (urlList.length === 0) {
      setError('Please enter at least one URL');
      setLoading(false);
      return;
    }

    try {
      const response = await axios.post('/api/feeds', { urls: urlList });
      setResults(response.data.results);
      
      // If all feeds were added successfully, navigate back
      if (response.data.status === 201) {
        setTimeout(() => navigate('/'), 2000);
      }
    } catch (err) {
      // Handle the case where feeds already exist
      if (err.response?.data?.results) {
        setResults(err.response.data.results);
      } else if (err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to add feeds. Please check the URLs and try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <Card>
      <Card.Body>
        <Card.Title>Add New RSS Feeds</Card.Title>
        <Form onSubmit={handleSubmit}>
          <Form.Group className="mb-3">
            <Form.Label>Feed URLs (one per line)</Form.Label>
            <Form.Control
              as="textarea"
              rows={5}
              placeholder="Enter RSS feed URLs (one per line)"
              value={urls}
              onChange={(e) => setUrls(e.target.value)}
              required
            />
            <Form.Text className="text-muted">
              Enter the URLs of the RSS feeds you want to add, one per line
            </Form.Text>
          </Form.Group>

          {error && (
            <Alert variant="danger" className="mb-3">
              {error}
            </Alert>
          )}

          {results && (
            <div className="mb-3">
              {results.map((result, index) => (
                <Alert 
                  key={index} 
                  variant={result.message === 'Feed already exists' ? 'info' : result.status >= 400 ? 'danger' : 'success'}
                  className="mb-2"
                >
                  <strong>{result.url}:</strong> {result.message}
                </Alert>
              ))}
            </div>
          )}

          <Button
            variant="primary"
            type="submit"
            disabled={loading}
          >
            {loading ? 'Adding...' : 'Add Feeds'}
          </Button>
        </Form>
      </Card.Body>
    </Card>
  );
}

export default AddFeed; 