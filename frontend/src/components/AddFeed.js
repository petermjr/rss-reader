import React, { useState } from 'react';
import { Form, Button, Card } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

function AddFeed() {
  const [url, setUrl] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      await axios.post('/api/feeds', { url });
      navigate('/');
    } catch (err) {
      if (err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to add feed. Please check the URL and try again.');
      }
      setLoading(false);
    }
  };

  return (
    <Card>
      <Card.Body>
        <Card.Title>Add New RSS Feed</Card.Title>
        <Form onSubmit={handleSubmit}>
          <Form.Group className="mb-3">
            <Form.Label>Feed URL</Form.Label>
            <Form.Control
              type="url"
              placeholder="Enter RSS feed URL"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              required
            />
            <Form.Text className="text-muted">
              Enter the URL of the RSS feed you want to add
            </Form.Text>
          </Form.Group>

          {error && <div className="text-danger mb-3">{error}</div>}

          <Button
            variant="primary"
            type="submit"
            disabled={loading}
          >
            {loading ? 'Adding...' : 'Add Feed'}
          </Button>
        </Form>
      </Card.Body>
    </Card>
  );
}

export default AddFeed; 